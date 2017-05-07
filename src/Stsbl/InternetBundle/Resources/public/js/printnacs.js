/*
 * The MIT License
 *
 * Copyright 2017 Felix Jacobi.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

IServ.PrintNacs = IServ.register(function(IServ) {

    // The managed fields will be hidden by default
    var formName = 'nac_create';
    var managedFieldNames = ['user', 'group', 'count'];
    var determiningFieldName = 'assignment'; // Radio buttons
    // Which field is displayed for which value:
    var dependencies = {
        'user': 'user',
        'group': 'group',
        'free_usage': 'count'
    };

    function refreshFormDependencies()
    {
        var selection = $("input[name='" + formName + "[" + determiningFieldName + "]']:checked").val();
        managedFieldNames.forEach(function (name) {
            var formGroup = $("[name='" + formName + "[" + name + "]']").parents('.form-group');
            formGroup.toggle(dependencies[selection] !== undefined && dependencies[selection] === name);
        });
    }

    function bindFormDependencies()
    {
        // Multiple because they're radios :/
        $("input[name='" + formName + "[" + determiningFieldName + "]']").on('click change', refreshFormDependencies);
    }

    function initialize()
    {
        bindFormDependencies();
        refreshFormDependencies();
    }

    return {
        init: initialize
    };

}(IServ));
