# XtraLife for MODX

User integration for the XtraLife game platform. This package includes a custom MODX User type (xlUser) and various other bits and pieces to allow managing XtraLife users from a MODX site.

## Installation from git

- Install dependencies in `core/components/xtralife/` with `composer install`
- Create a `config.core.php` in the project root, see the `config.core.sample.php` for an example.
- Create `core/components/xtralife/.env` from the example `core/components/xtralife/.env.example`
  - To create a key to safely store the gamer_secret, SSH to the server, `cd core/components/xtralife/` and run `vendor/bin/generate-defuse-key | base64`. Remove the line breaks and save to your .env file.
- Run the bootstrap: `php _bootstrap/index.php`

## Usage instructions

### Register

Add postHook `XtraLifeRegister`, preHook `XtraLifePreRegister`, and use `email` for the username: 

```` 
[[!Register?
    ...
    &preHooks=`XtraLifePreRegister` 
    &postHooks=`XtraLifeRegister` 
    &usernameField=`email`
    &validate=`nospam:blank,
      password:required:minLength=^6^,
      email:required:email`
]]
````

Make sure the `username` is set as an email. E.g. ```&usernameField=`email` ``` + validate it as an email address.

### Login

No customisations needed; automatically uses extended passwordMatches method. 

Example form showing the loaded profile outline:

```html
[[!Login]]

[[!+outline.profile.displayName:notempty=`<p>Hello <b>[[!+outline.profile.displayName:htmlent]]</b>!</p>`]]
[[!+outline_dump]]
```

### Reset password 

This uses the XtraLife API to email a shortcode which the user then enters alongside their new password. 

```html
[[!XtraLifeReset]]
```

This snippet has a couple of optional properties to use:

- `tplEnterEmail`: name of a chunk to render the form where the user is asked to provide the email to reset for. `[[+csrf_token]]` is required in the form.
- `tplEnterCode`: name of a chunk to render the form where the user is asked to provide the shortcode they received in the email, plus their new password. `[[+csrf_token]]` is required in the form.
- `tplSuccess`: name of a chunk to render a success message when the password has been changed and the user is logged in.
- `tplError`: name of a chunk to render an error message, provided in `[[+message]]`. Recommended to add a link back to itself so the user can try again.
- `emailFrom`: the from email address, defaults to the emailsender system setting in MODX.
- `emailTitle`: subject for the email, defaults to: `Reset your password`
- `emailBody`: simple body for the email, needs the `[[SHORTCODE]]` placeholder. Defaults to: `Your password reset code is: [[SHORTCODE]]`

Default chunks are provided in core/components/xtralife/elements/chunks/reset/.

## Password Change

No changes are needed to the standard form, password changes are handled through the extended user type automatically. Example:

```html
<h2>Change Password</h2>
[[!ChangePassword?
   &submitVar=`change-password`
   &placeholderPrefix=`cp.`
   &validateOldPassword=`1`
   &validate=`nospam:blank`
]]
<div class="updprof-error">[[!+cp.error_message]]</div>
<form class="form" action="[[~[[*id]]]]" method="post">
    <input type="hidden" name="nospam" value="" />
    <div class="ff">
        <label for="password_old">Old Password
            <span class="error">[[!+cp.error.password_old]]</span>
        </label>
        <input type="password" name="password_old" id="password_old" value="[[+cp.password_old]]" />
    </div>
    <div class="ff">
        <label for="password_new">New Password
            <span class="error">[[!+cp.error.password_new]]</span>
        </label>
        <input type="password" name="password_new" id="password_new" value="[[+cp.password_new]]" />
    </div>
    <div class="ff">
        <label for="password_new_confirm">Confirm New Password
            <span class="error">[[!+cp.error.password_new_confirm]]</span>
        </label>
        <input type="password" name="password_new_confirm" id="password_new_confirm" value="[[+cp.password_new_confirm]]" />
    </div>
    <div class="ff">
        <input type="submit" name="change-password" value="Change Password" />
    </div>
</form>
```

## Update Profile

The integration will adjust the email address in XtraLife if that was changed. Requires adding `XtraLifeUpdateProfile` to the postHooks property and ```&syncUsername=`email` ```. Example form:


```html
[[!UpdateProfile? &validate=`fullname:required,email:required:email` &postHooks=`XtraLifeUpdateProfile` &syncUsername=`email`]]

<div class="update-profile">
    <div class="updprof-error">[[+error.message]]</div>
    [[!+login.update_success:is=`1`:then=`[[%login.profile_updated? &namespace=`login` &topic=`updateprofile`]]`]]

    <form class="form" action="[[~[[*id]]]]" method="post">
        <input type="hidden" name="nospam" value="" />

        <div>
            <label for="fullname">[[!%login.fullname? &namespace=`login` &topic=`updateprofile`]]
                <span class="error">[[!+error.fullname]]</span>
            </label>
            <input type="text" name="fullname" id="fullname" value="[[!+fullname]]" />
        </div>
        <div>
            <label for="email">[[!%login.email]]
                <span class="error">[[!+error.email]]</span>
            </label>
            <input type="text" name="email" id="email" value="[[!+email]]" />
        </div>
        <br class="clear" />

        <div class="form-buttons">
            <input type="submit" name="login-updprof-btn" value="[[!%login.update_profile]]" />
        </div>
    </form>
</div>
```
