<?php

namespace Foolz\Foolframe\Theme\Admin\Partial\Account;

class RequestDelete extends \Foolz\Theme\View
{
    public function toString()
    { ?>
<div class="admin-container">
    <div class="admin-container-header"><?= _i('New Email Address') ?></div>
    <p>
        <i class="icon-warning-sign text-error"></i> <?= _i('Since this action is irreversible, a link will be sent to the email associated with your account to verify your decision to purge your account from the system.') ?>

        <hr/>

        <?= \Form::open(['onsubmit' => 'fuel_set_csrf_token(this);']) ?>
        <?= \Form::hidden(\Config::get('security.csrf_token_key'), \Security::fetch_token()) ?>

        <div class="control-group">
            <label class="control-label" for="password"><?= _i('Password') ?></label>
            <div class="controls">
                <?= \Form::password([
                    'id' => 'password',
                    'name' => 'password',
                    'placeholder' => _i('Password'),
                    'required' => true
                ]) ?>
            </div>
        </div>

        <div class="control-group">
            <div class="controls">
                <?= \Form::submit(['class' => 'btn btn-primary', 'name' => 'submit', 'value' => _i('Request Account Deletion')]) ?>
            </div>
        </div>

        <?= \Form::close() ?>
    </p>
</div>
    <?php
    }
}
