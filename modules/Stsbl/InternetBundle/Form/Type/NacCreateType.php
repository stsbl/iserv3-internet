<?php
// src/Stsbl/InternetBundle/Form/Type/Nac.php
namespace Stsbl\InternetBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use IServ\CoreBundle\Form\Type\GroupType;
use IServ\CoreBundle\Form\Type\UserType;
use Stsbl\InternetBundle\Form\Data\CreateNacs;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
            ->add('duration', IntegerType::class, [
                'label' => _('Duration'),
                'data' => $options['default_credits'],
                'attr' => [
                    'min' => 1,
                    'max' => self::MAX_NAC_VALUE,
                    'input_group' => [
                        'append' => _n('minute', 'minutes', 5) // random
                    ],
                ],
            ])
            ->add('assignment', ChoiceType::class, [
                'label' => _('For'),
                'expanded' => true,
                'required' => true,
                'choices' => [
                    _('free usage') => 'free_usage',
                    _('user') => 'user',
                    _('all members of a group') => 'group',
                    _('all users') => 'all',
                ],
            ])
            // The following fields have conditional constraints based on the value of the 'assignment'-field.
            // The fields are also toggled using JavaScript according to these dependencies.
            ->add('user', UserType::class, [
                'multiple' => false,
                'order_by' => null,
                'query_builder' => function(EntityRepository $er) {
                    // Manually filter deleted entries due to #1124. I have seriously no f***ing clue. :(
                    return $er->createQueryBuilder('u')->andWhere('u.deleted IS NULL')->orderBy('u.firstname, u.lastname', 'ASC');
                },
            ])
            ->add('group', GroupType::class, [
                'multiple' => false,
                'order_by' => null,
                'query_builder' => function(EntityRepository $er) {
                    // Manually filter deleted entries due to #1124. I have seriously no f***ing clue. :(
                    return $er->createQueryBuilder('g')->select('g, LOWER(g.name) AS HIDDEN lcName')->andWhere('g.deleted IS NULL')->orderBy('lcName', 'ASC');
                },
            ])
            ->add('count', IntegerType::class, [
                'label' => _('Number'),
                'data' => 1,
            ])
            ->add('submit', SubmitType::class, ['label' => _('Create'), 'buttonClass' => 'btn-success', 'icon' => 'ok'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('default_credits', 0);
        $resolver->setDefault('data_class', CreateNacs::class);
    }

    public function getBlockPrefix()
    {
        return 'nac_create';
    }

}
