<p>An email has been sent to you with a reset code. Please enter it below with a new password.</p>
<form action="[[~[[*id]]]]" method="post">
    <input type="hidden" name="csrf_token" value="[[+csrf_token]]">

    <div class="form-field">
        <label for="reset-shortcode">Reset code:</label>
        <input type="text" id="reset-shortcode" name="code">
    </div>
    <div class="form-field">
        <label for="reset-new-password">New password:</label>
        <input type="password" id="reset-new-password" name="new_password">
    </div>

    <div class="form-actions">
        <button type="submit">Set new password</button>
    </div>
</form>