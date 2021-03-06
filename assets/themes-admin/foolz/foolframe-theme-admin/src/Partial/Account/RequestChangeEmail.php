<?php

namespace Foolz\FoolFrame\Theme\Admin\Partial\Account;

class RequestChangeEmail extends \Foolz\FoolFrame\View\View
{
    public function toString()
    {
        $form = $this->getForm();
        ?>
<div class="admin-container">
    <div class="admin-container-header"><?= _i('Change Email Address') ?></div>
    <p>
        <?= $form->open(['onsubmit' => 'fuel_set_csrf_token(this);']) ?>
        <?= $form->hidden('csrf_token', $this->getSecurity()->getCsrfToken()) ?>

        <div class="control-group">
            <label class="control-label" for="new-email"><?= _i('New Email Address') ?></label>
            <div class="controls">
                <?= $form->input([
                    'id' => 'new-email',
                    'name' => 'email',
                    'type' => 'email',
                    'value' => $this->getPost('email'),
                    'placeholder' => 'test@example.com',
                    'required' => true
                ]) ?>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="password"><?= _i('Password') ?></label>
            <div class="controls">
                <?= $form->password([
                    'id' => 'password',
                    'name' => 'password',
                    'placeholder' => _i('Password'),
                    'required' => true
                ]) ?>
            </div>
        </div>

        <div class="control-group">
            <div class="controls">
                <?= $form->submit(['class' => 'btn btn-primary', 'name' => 'submit', 'value' => _i('Submit')]) ?>
            </div>
        </div>

        <?= $form->close() ?>
    </p>
</div>
<?php
    }
}
