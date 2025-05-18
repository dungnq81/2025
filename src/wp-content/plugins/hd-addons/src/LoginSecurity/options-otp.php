<form method="POST" style="text-align:center; max-width: 400px; margin: 0 auto;">
    <h2>Email OTP Verification</h2>
    <p>A 6-digit code has been sent to your email. Enter it below:</p>

    <input type="text" name="otp_input" placeholder="Enter OTP" required maxlength="6" pattern="\d{6}" style="text-align:center;"><br><br>

    <button type="submit" name="otp_verify">Verify OTP</button>
    <button type="submit" name="resend_otp" id="resendBtn" disabled>Resend OTP (<span id="countdown">300</span>s)</button>
</form>
