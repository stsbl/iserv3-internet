<?php
// src/Stsbl/InternetBundle/Form/Type/Nac.php
namespace Stsbl\InternetBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use IServ\CoreBundle\Form\Type\GroupType;
use IServ\CoreBundle\Form\Type\UserType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Form for nac manager for create new NACs
 *
 * Used in NAC-CRUD index action
 */
class NacCreateType extends AbstractType
{
    const MAX_NAC_VALUE = 99999;
    const MAX_UNASSIGNED_NAC_COUNT = 100;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('value', IntegerType::class, array(
                'label' => _('Duration'),
                'data' => $options['default_credits'],
                'attr' => array(
                    'min' => 1,
                    'max' => self::MAX_NAC_VALUE,
                    'input_group' => array(
                        'append' => _n('minute', 'minutes', 5) // random
                    ),
                ),
                'constraints' => array(
                    new NotBlank(),
                    new GreaterThan(0),
                    new LessThanOrEqual(self::MAX_NAC_VALUE),
                ),
            ))
            ->add('assignment', ChoiceType::class, array(
                'label' => _('For'),
                'expanded' => true,
                'required' => true,
                'choices' => array(
                    _('free usage') => 'free_usage',
                    _('user') => 'user',
                    _('all members of a group') => 'group',
                    _('all users') => 'all',
                ),
                'choices_as_values' => true,
            ))
            // The following fields have conditional constraints based on the value of the 'assignment'-field.
            // The fields are also toggled using JavaScript according to these dependencies.
            ->add('user', UserType::class, array(
                'multiple' => false,
                'order_by' => null,
                'query_builder' => function(EntityRepository $er) {
                    // Manually filter deleted entries due to #1124. I have seriously no f***ing clue. :(
                    return $er->createQueryBuilder('u')->andWhere('u.deleted IS NULL')->orderBy('u.firstname, u.lastname', 'ASC');
                },
                'constraints' => new Callback(function($value, ExecutionContextInterface $context) {
                    $form = $context->getRoot();
                    $data = $form->getData();
                    if ($data['assignment'] === 'user' && $value === null) {
                        self::addViolationToContext($context, _('Please choose a user'));
                    }
                }),
            ))
            ->add('group', GroupType::class, array(
                'multiple' => false,
                'order_by' => null,
                'query_builder' => function(EntityRepository $er) {
                    // Manually filter deleted entries due to #1124. I have seriously no f***ing clue. :(
                    return $er->createQueryBuilder('g')->select('g, LOWER(g.name) AS HIDDEN lcName')->andWhere('g.deleted IS NULL')->orderBy('lcName', 'ASC');
                },
                'constraints' => new Callback(function($value, ExecutionContextInterface $context) {
                    $form = $context->getRoot();
                    $data = $form->getData();
                    if ($data['assignment'] === 'group' && $value === null) {
                        self::addViolationToContext($context, _('Please choose a group'));
                    }
                }),
            ))
            ->add('count', IntegerType::class, array(
                'label' => _('Number'),
                'data' => 1,
                // (Can't use HTML5 max/min attributes here because fields are not always required.)
                'constraints' => new Callback(function($value, ExecutionContextInterface $context) {
                    $form = $context->getRoot();
                    $data = $form->getData();
                    if ($data['assignment'] === 'free_usage') {
                        if ($value === null || ! is_numeric($value) || $value <= 0) {
                            self::addViolationToContext($context, __('This value should be greater than %s.', 0));
                        }
                        else if ($value > self::MAX_UNASSIGNED_NAC_COUNT) {
                            self::addViolationToContext($context, __('This value should be less than or equal to %s.', self::MAX_UNASSIGNED_NAC_COUNT));
                        }
                    }
                }),
            ))
            ->add('submit', SubmitType::class, array('label' => _('Create'), 'buttonClass' => 'btn-success', 'icon' => 'ok'))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('default_credits', 0);
    }

    /**
     * Hack to prevent double-translation of violation messages.
     *
     * @param ExecutionContextInterface $context
     * @param string $translatedMessage
     */
    private static function addViolationToContext(ExecutionContextInterface $context, $translatedMessage)
    {
        $context->buildViolation('%msg%')->setParameter('%msg%', $translatedMessage)->addViolation();
    }

    public function getBlockPrefix()
    {
        return 'nac_create';
    }

}
