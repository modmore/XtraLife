<p>If you've forgotten your password, you may set a new password here. Please start by entering your emailaddress, we will send a reset code to your email.</p>
<form action="[[~[[*id]]]]" method="post">
    <input type="hidden" name="csrf_token" value="[[+csrf_token]]">

    <div class="form-field">
        <label for="reset-email">Email address:</label>
        <input type="text" id="reset-email" name="reset_email">
    </div>

    <div class="form-actions">
        <button type="submit">Send reset email</button>
    </div>
</form>