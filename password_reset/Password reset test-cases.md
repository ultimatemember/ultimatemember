# Password Reset

This document contains information on how the Ultimate Member plugin performs a password reset using the Reset password form in the provided flows and what is the expected result for each use case.

### Contents

 - [Visibility of the "Password reset" form on the site for logged in users](#visibility-of-the-password-reset-form-on-the-site-for-logged-in-users)
 - [Visibility of the "Password reset" form on the site for the guest](#visibility-of-the-password-reset-form-on-the-site-for-the-guest)
 - [Reset password by entering username](#reset-password-by-entering-username)
 - [Reset password by entering email address](#reset-password-by-entering-email-address)
 - [As a guest, reset the password for the selected user and check for session clearing in browsers for that user](#as-a-guest-reset-the-password-for-the-selected-user-and-check-for-session-clearing-in-browsers-for-that-user)
 - [As a logged in user, reset their password and check to clear the current session and sessions in other browsers](#as-a-logged-in-user-reset-their-password-and-check-to-clear-the-current-session-and-sessions-in-other-browsers)
 - [As a logged in user, reset the password for another user and check that the current user's sessions are not cleared](#as-a-logged-in-user-reset-the-password-for-another-user-and-check-that-the-current-users-sessions-are-not-cleared)
 - [As a logged in user, reset the password for the other user, and check that the session is cleared for the user who confirmed the password reset](#as-a-logged-in-user-reset-the-password-for-the-other-user-and-check-that-the-session-is-cleared-for-the-user-who-confirmed-the-password-reset)
 - [Using the character "backslash" for a new password in the password reset form](#using-the-character-backslash-for-a-new-password-in-the-password-reset-form)
 - [Using less than the required number of characters for a new password in the password reset form](#using-less-than-the-required-number-of-characters-for-a-new-password-in-the-password-reset-form)
 - [Using more than the required number of characters for a new password in the password reset form](#using-more-than-the-required-number-of-characters-for-a-new-password-in-the-password-reset-form)
 - [Using only lowercase letters for the new password in the password reset form](#using-only-lowercase-letters-for-the-new-password-in-the-password-reset-form)
 - [Leave the password confirmation field blank in the password reset form](#leave-the-password-confirmation-field-blank-in-the-password-reset-form)
 - [Using mismatched passwords for fields on the password reset form](#using-mismatched-passwords-for-fields-on-the-password-reset-form)
 - [Leave the fields for creating a new password in the password reset form blank](#leave-the-fields-for-creating-a-new-password-in-the-password-reset-form-blank)
 - [Set a password reset limit](#set-a-password-reset-limit)


## Visibility of the "Password reset" form on the site for logged in users.

Pre-conditions to reproduce the test case:

1. **Ultimate Member v3** plugin must be activated.
2. The "Password Reset page" must be assigned and saved in the Ultimate Member settings (WP-Admin > Ultimate Member > Settings > General > Pages - Password Reset page)
3. Create or register a user.

Steps to reproduce the test case:

1. Go to the site and log in as a user.
2. Go to "Password Reset" page - check that notice and password reset form are displayed to the user.

Expected result:

 - The user is successfully authorized on the site.
 - The "Password Reset" page displays a notice: 
 >"Please enter your username or email address. You will receive an email message with instructions on how to reset your password."
 - The "Password Reset" page displays the "Username or Email Address*:" field and the "Get new password" button.
 - The "Reset password" form is displayed on the site for the logged in user.

[Screencast Visibility of the "Password reset" form on the site for logged in users](https://www.dropbox.com/s/8ygw9bqy2z2a44c/01.%20Visibility%20of%20the%20Password%20reset%20form%20on%20the%20site%20for%20logged%20in%20users.mp4?dl=0)


## Visibility of the "Password reset" form on the site for the guest.

Pre-conditions to reproduce the test case:

1. **Ultimate Member v3** plugin must be activated
2. The "Password Reset page" must be assigned and saved in the Ultimate Member settings (WP-Admin > Ultimate Member > Settings > General > Pages - Password Reset page)

Steps to reproduce the test case:

1. Go to the site as a guest.
2. Go to "Password Reset" page - check that notice and password reset form are displayed to the guest.

Expected result:

 - The "Password Reset" page displays a notice: 
 >"Please enter your username or email address. You will receive an email message with instructions on how to reset your password."
 - The "Password Reset" page displays the "Username or Email Address*:" field and the "Get new password" button.
 - The "Reset password" form is displayed on the site for the guest.

[Screencast Visibility of the "Password reset" form on the site for the guest](https://www.dropbox.com/s/y0zedoiky54udgw/02.%20Visibility%20of%20the%20Password%20reset%20form%20on%20the%20site%20for%20the%20guest.mp4?dl=0)


## Reset password by entering username.

Pre-conditions to reproduce the test case:

1. [Visibility of the "Password reset" form on the site for logged in users](#visibility-of-the-password-reset-form-on-the-site-for-logged-in-users)
2. Email notification "Password Reset Email" must be enabled in the Ultimate Member settings (WP-Admin > Ultimate Member > Settings > Email - "Password Reset Email" notification)

Steps to reproduce the test case:

1. Go to the site and log in as a user > Go to the "Password Reset" page
2. In the field "Username or Email Address*:" enter the username of the current user > Click on the "Get new password" button
3. Check the mailbox of the current user whose username was specified when resetting the password > Open the received letter (Mail:Reset your password) and confirm the password reset by clicking on the "Reset your password" button.
4. On the "Password Reset" page, enter a new password in the "New Password*:","Confirm Password*:" fields and click on the "Save Password" button.
5. Return to the browser tab with the current user's session open and refresh the page - check that the session has ended for an authorized user whose password has been reset by entering username.

Expected result:

 - The user is successfully authorized on the site.
 - After clicking on the "Get new password" button on the "Password Reset" page, a notice is displayed:
 >"If an account matching the provided details exists, we will send a password reset link. Check your email for the confirmation link, then visit the login page."
 - After entering username in the "Username or Email Address*:" field and clicking on the "Get new password" button, an email is sent to the user (Mail:Reset your password)
 - After clicking on the "Reset your password" button in the received email, the user is redirected to the "Password Reset" page.
 - The "Password Reset" page displays two fields "New Password*:","Confirm Password*:" and the "Save Password" button, as well as notice:
 >"Enter your new password below and confirm it."
 - After clicking on the "Save Password" button on the "Password Reset" page, a notice is displayed:
 >"Your password has been reset. Log in."
 - When resetting a password using the username of a user and saving the new password, the session previously opened by that user is cleared. The user needs to log in again with a new password.

[Screencast Reset password by entering username](https://www.dropbox.com/s/aulc16l0n4pbesh/03.%20Reset%20password%20by%20entering%20username.mp4?dl=0)


## Reset password by entering email address.

Pre-conditions to reproduce the test case:

1. [Visibility of the "Password reset" form on the site for logged in users](#visibility-of-the-password-reset-form-on-the-site-for-logged-in-users)
2. Email notification "Password Reset Email" must be enabled in the Ultimate Member settings (WP-Admin > Ultimate Member > Settings > Email - "Password Reset Email" notification)

Steps to reproduce the test case:

1. Go to the site and log in as a user > Go to the "Password Reset" page
2. In the field "Username or Email Address*:" enter the email address of the current user > Click on the "Get new password" button
3. Check the mailbox of the current user whose email address was specified when resetting the password > Open the received letter (Mail:Reset your password) and confirm the password reset by clicking on the "Reset your password" button.
4. On the "Password Reset" page, enter a new password in the "New Password*:","Confirm Password*:" fields and click on the "Save Password" button.
5. Return to the browser with the current user's session open and refresh the page - check that the session has ended for an authorized user whose password has been reset by entering an email address.

Expected result:

 - The user is successfully authorized on the site.
 - After clicking on the "Get new password" button on the "Password Reset" page, a notice is displayed:
 >"If an account matching the provided details exists, we will send a password reset link. Check your email for the confirmation link, then visit the login page."
 - After entering email in the "Username or Email Address*:" field and clicking on the "Get new password" button, an email is sent to the user (Mail:Reset your password)
 - After clicking on the "Reset your password" button in the received email, the user is redirected to the "Password Reset" page.
 - The "Password Reset" page displays two fields "New Password*:","Confirm Password*:" and the "Save Password" button, as well as notice:
 >"Enter your new password below and confirm it."
 - After clicking on the "Save Password" button on the "Password Reset" page, a notice is displayed:
 >"Your password has been reset. Log in."
 - When resetting a password using a user's email address and saving the new password, the session previously opened by that user is cleared. The user needs to log in again with a new password.

[Screencast Reset password by entering email address](https://www.dropbox.com/s/tgekxdlzo36oidy/04.%20Reset%20password%20by%20entering%20email%20address.mp4?dl=0)


## As a guest, reset the password for the selected user and check for session clearing in browsers for that user.

Pre-conditions to reproduce the test case:

1. [Visibility of the "Password reset" form on the site for logged in users](#visibility-of-the-password-reset-form-on-the-site-for-logged-in-users)
2. Email notification "Password Reset Email" must be enabled in the Ultimate Member settings (WP-Admin > Ultimate Member > Settings > Email - "Password Reset Email" notification)

Steps to reproduce the test case:

1. Go to the site and log in as the user for whom the password will be reset > Leave the session open
2. Open second browser > Go to the site as a guest > Go to the "Password Reset" page
3. In the "Username or Email Address*:" field, enter the username or email address of the user for whom the session is open > Click on the "Get new password" button
4. Return to the first browser and refresh the page - check that the user session is active and the user is logged in.
5. Check the mailbox of the user that was specified when resetting the password > Open the received letter (Mail:Reset your password) and confirm the password reset by clicking on the "Reset your password" button.
6. On the "Password Reset" page, enter a new password in the "New Password*:","Confirm Password*:" fields and click on the "Save Password" button.
7. Return to the first browser with the current user's session open and refresh the page - check that the session is cleared for an authorized user for whom the password has been reset and he is logged out of the account.

Expected result:

 - The user is successfully authorized on the site.
 - After clicking on the "Get new password" button on the "Password Reset" page, a notice is displayed:
 >"If an account matching the provided details exists, we will send a password reset link. Check your email for the confirmation link, then visit the login page."
 - After clicking on the "Reset your password" button in the received email, the user is redirected to the "Password Reset" page.
 - The "Password Reset" page displays two fields "New Password*:","Confirm Password*:" and the "Save Password" button, as well as notice:
 >"Enter your new password below and confirm it."
 - After clicking on the "Save Password" button, the login page opens.
 - Guest can successfully submit a password reset request for a selected user by entering a username or email address in the password reset form.
 - The sessions of a user whose password has been reset are cleared only after the reset is confirmed and the new password is saved.

[Screencast As a guest, reset the password for the selected user and check for session clearing in browsers for that user](https://www.dropbox.com/s/h313zdb6q14mlxl/05.%20As%20a%20guest%2C%20reset%20the%20password%20for%20the%20selected%20user%20and%20check%20for%20session%20clearing%20in%20browsers%20for%20that%20user.mp4?dl=0)


## As a logged in user, reset their password and check to clear the current session and sessions in other browsers.

Pre-conditions to reproduce the test case:

1. [Visibility of the "Password reset" form on the site for logged in users](#visibility-of-the-password-reset-form-on-the-site-for-logged-in-users)
2. Email notification "Password Reset Email" must be enabled in the Ultimate Member settings (WP-Admin > Ultimate Member > Settings > Email - "Password Reset Email" notification)

Steps to reproduce the test case:

1. Open first browser > Go to the site and log in as the user for whom the password will be reset > Leave the session open
2. Open second browser > Go to the site and log in with the same user for which the password will be reset > Go to the "Password Reset" page
3. In the field "Username or Email Address*:" enter the username or email address of the current user for which sessions are opened in browsers > Click on the "Get new password" button
4. Check the mailbox of the current user that was specified when resetting the password > Open the received letter (Mail:Reset your password) and confirm the password reset by clicking on the "Reset your password" button.
5. On the "Password Reset" page, enter a new password in the "New Password*:","Confirm Password*:" fields and click on the "Save Password" button.
6. Return to the second browser with an open session from which the password was reset for the current user and refresh the page - check that the session is cleared for an authorized user for whom the password has been reset and he is logged out of the account.
7. Return to the first browser with an open session of the current user whose password was reset and refresh the page - check that the session is cleared for an authorized user for whom the password has been reset and he is logged out of the account.

Expected result:

 - The user is successfully authorized on the site.
 - After clicking on the "Get new password" button on the "Password Reset" page, a notice is displayed:
 >"If an account matching the provided details exists, we will send a password reset link. Check your email for the confirmation link, then visit the login page."
 - After clicking on the "Reset your password" button in the received email, the user is redirected to the "Password Reset" page.
 - After clicking on the "Save Password" button on the "Password Reset" page, a notice is displayed:
 >"Your password has been reset. Log in."
 - All active sessions of the user for which the password is reset are cleared after the reset is confirmed and the new password is saved.

[Screencast As a logged in user, reset their password and check to clear the current session and sessions in other browsers](https://www.dropbox.com/s/4jgkfcxq9d9h3v5/06.%20As%20a%20logged%20in%20user%2C%20reset%20their%20password%20and%20check%20to%20clear%20the%20current%20session%20and%20sessions%20in%20other%20browsers.mp4?dl=0)


## As a logged in user, reset the password for another user and check that the current user's sessions are not cleared.

Pre-conditions to reproduce the test case:

1. [Visibility of the "Password reset" form on the site for logged in users](#visibility-of-the-password-reset-form-on-the-site-for-logged-in-users)
2. Email notification "Password Reset Email" must be enabled in the Ultimate Member settings (WP-Admin > Ultimate Member > Settings > Email - "Password Reset Email" notification)

Steps to reproduce the test case:

1. Open first browser > Go to the site and log in > Leave the session open
2. Open second browser > Go to the site and log in with the same user for which the session is opened in the first browser > Go to the "Password Reset" page
3. In the "Username or Email Address*:" field, enter the username or email address of another user > Click on the "Get new password" button
4. Check the mailbox of the user that was specified when resetting the password > Open the received letter (Mail:Reset your password) and confirm the password reset by clicking on the "Reset your password" button.
5. On the "Password Reset" page, enter a new password in the "New Password*:","Confirm Password*:" fields and click on the "Save Password" button.
6. Return to the second browser with an open session from which the password was reset for another user and refresh the page - check that the session is active for the authorized user and he remained logged in.
7. Return to the first browser with the same user session open and refresh the page - check that the session is active for the authorized user and he remained logged in.

Expected result:

 - The user is successfully authorized on the site.
 - After clicking on the "Get new password" button on the "Password Reset" page, a notice is displayed:
 >"If an account matching the provided details exists, we will send a password reset link. Check your email for the confirmation link, then visit the login page."
 - After clicking on the "Reset your password" button in the received email, the user is redirected to the "Password Reset" page.
 - After clicking on the "Save Password" button, the login page opens.
 - User can successfully submit a password reset request for another user by entering a username or email address in the password reset form.
 - The sessions of a user who submits a password reset request for another user remain active and are not cleared even when the other user confirms the password reset and saves the new password.

[Screencast As a logged in user, reset the password for another user and check that the current user's sessions are not cleared]( https://www.dropbox.com/s/2et6ibv97o9aw24/07.%20As%20a%20logged%20in%20user%2C%20reset%20the%20password%20for%20another%20user%20and%20check%20that%20the%20current%20user%27s%20sessions%20are%20not%20cleared.mp4?dl=0)


## As a logged in user, reset the password for the other user, and check that the session is cleared for the user who confirmed the password reset.

Pre-conditions to reproduce the test case:

1. [Visibility of the "Password reset" form on the site for logged in users](#visibility-of-the-password-reset-form-on-the-site-for-logged-in-users)
2. Email notification "Password Reset Email" must be enabled in the Ultimate Member settings (WP-Admin > Ultimate Member > Settings > Email - "Password Reset Email" notification)

Steps to reproduce the test case:

1. Open first browser > Go to the site and log in as user 1, for which the password will be reset > Leave the session open
2. Open second browser > Go to the site and log in as another user 2 > Go to the "Password Reset" page
3. In the field "Username or Email Address*:" enter the username or email address of user 1 > Click on the "Get new password" button.
4. Check the mailbox of user 1, which was specified during the password reset > Open the received letter (Mail:Reset your password) and confirm the password reset by clicking on the "Reset your password" button.
5. On the "Password Reset" page, enter a new password in the "New Password*:","Confirm Password*:" fields and click on the "Save Password" button.
6. Back to second browser with user 2 open session that reset password for user 1 - check that the session is active for the authorized user and he remained logged in.
7. Return to first browser with open user 1 session - check that the session is cleared for an authorized user for whom the password has been reset and he is logged out of the account.

Expected result:

 - The user is successfully authorized on the site.
 - After clicking on the "Get new password" button on the "Password Reset" page, a notice is displayed:
 >"If an account matching the provided details exists, we will send a password reset link. Check your email for the confirmation link, then visit the login page."
 - After clicking on the "Reset your password" button in the received email, the user is redirected to the "Password Reset" page.
 - After clicking on the "Save Password" button, the login page opens.
 - User can successfully submit a password reset request for another user by entering a username or email address in the password reset form.
 - An active user session that submits a password reset request for another user remains active and is not cleared.
 - The active user session for which the password is reset is cleared after the reset is confirmed and the new password is saved.

[Screencast As a logged in user, reset the password for the other user, and check that the session is cleared for the user who confirmed the password reset](https://www.dropbox.com/s/bfhyhhmcnqadmm3/08.%20As%20a%20logged%20in%20user%2C%20reset%20the%20password%20for%20the%20other%20user%2C%20and%20check%20that%20the%20session%20is%20cleared%20for%20the%20user%20who%20confirmed%20the%20password%20reset.mp4?dl=0)


## Using the character "backslash" for a new password in the password reset form.

Pre-conditions to reproduce the test case:

1. [Visibility of the "Password reset" form on the site for logged in users](#visibility-of-the-password-reset-form-on-the-site-for-logged-in-users)
2. Email notification "Password Reset Email" must be enabled in the Ultimate Member settings (WP-Admin > Ultimate Member > Settings > Email - "Password Reset Email" notification)

Steps to reproduce the test case:

1. Go to the site and log in as a user > Go to the "Password Reset" page
2. In the "Username or Email Address*:" field, enter the username or email address of the user > Click on the "Get new password" button
3. Check the mailbox of the user whose username or email address was specified when resetting the password > Open the received letter (Mail:Reset your password) and confirm the password reset by clicking on the "Reset your password" button.
4. On the "Password Reset" page, enter a new password that contains the "backslash" character in the "New Password*:","Confirm Password*:" fields and click on the "Save Password" button - check that notice is displayed under the "New Password*:" field.

Expected result:

 - The user is successfully authorized on the site.
 - After clicking on the "Get new password" button on the "Password Reset" page, a notice is displayed:
 >"If an account matching the provided details exists, we will send a password reset link. Check your email for the confirmation link, then visit the login page."
 - After entering a username or email address in the "Username or Email Address*:" field and clicking on the "Get new password" button, an email is sent to the user (Mail:Reset your password)
 - After clicking on the "Reset your password" button in the received email, the user is redirected to the "Password Reset" page.
 - The "Password Reset" page displays two fields "New Password*:","Confirm Password*:" and the "Save Password" button, as well as notice:
 >"Enter your new password below and confirm it."
 - After clicking on the "Save Password" button on the "Password Reset" page, a notice is displayed under the "New Password*:" field:
 >"Passwords may not contain the character "backslash"."
 - The password has not been reset and the new password is incorrect because the "backslash" character was used to create the new password.
 - If there is a honeypot security issue, a notice will be displayed:
 >"Hello, spam bot!" 
 - If there is a problem with the cache or a wrong nonce, notice will be displayed:
 >"Security issue, Please try again"

[Screencast Using the character "backslash" for a new password in the password reset form](https://www.dropbox.com/s/eqj1dgy5u3b5f31/09.%20Using%20the%20character%20%28backslash%29%20for%20a%20new%20password%20in%20the%20Password%20reset%20form.mp4?dl=0)


## Using less than the required number of characters for a new password in the password reset form.

Pre-conditions to reproduce the test case:

1. [Visibility of the "Password reset" form on the site for logged in users](#visibility-of-the-password-reset-form-on-the-site-for-logged-in-users)
2. Email notification "Password Reset Email" must be enabled in the Ultimate Member settings (WP-Admin > Ultimate Member > Settings > Email - "Password Reset Email" notification)
3. Ultimate Member settings must have "Require Strong Passwords" enabled and set to "Password minimum length" (Default setting example - 8 characters)
   (WP-Admin > Ultimate Member > Settings > General > Users - Require Strong Passwords )

Steps to reproduce the test case:

1. Go to the site and log in as a user > Go to the "Password Reset" page
2. In the "Username or Email Address*:" field, enter the username or email address of the user > Click on the "Get new password" button.
3. Check the mailbox of the user whose username or email address was specified when resetting the password > Open the received letter (Mail:Reset your password) and confirm the password reset by clicking on the "Reset your password" button.
4. On the "Password Reset" page, enter a new password that contains fewer characters than specified in the "Password minimum length" setting, in the "New Password*:","Confirm Password*:" fields > Click on the "Save Password" button - check that notice is displayed under the "New Password*:" field.

Expected result:

 - The user is successfully authorized on the site.
 - After clicking on the "Get new password" button on the "Password Reset" page, a notice is displayed:
 >"If an account matching the provided details exists, we will send a password reset link. Check your email for the confirmation link, then visit the login page."
 - After entering a username or email address in the "Username or Email Address*:" field and clicking on the "Get new password" button, an email is sent to the user (Mail:Reset your password)
 - After clicking on the "Reset your password" button in the received email, the user is redirected to the "Password Reset" page.
 - The "Password Reset" page displays two fields "New Password*:","Confirm Password*:" and the "Save Password" button, as well as notice:
 >"Enter your new password below and confirm it."
 - After clicking on the "Save Password" button on the "Password Reset" page, a notice is displayed under the "New Password*:" field:
 >"Your password must contain at least ... characters"
 - The password was not reset because it contains fewer characters than the one specified in the "Password minimum length" setting.

[Screencast Using less than the required number of characters for a new password in the password reset form](https://www.dropbox.com/s/3k9ez63bebvlm0c/10.%20Using%20less%20than%20the%20required%20number%20of%20characters%20for%20a%20new%20password%20in%20the%20password%20reset%20form.mp4?dl=0)


## Using more than the required number of characters for a new password in the password reset form.

Pre-conditions to reproduce the test case:

1. [Visibility of the "Password reset" form on the site for logged in users](#visibility-of-the-password-reset-form-on-the-site-for-logged-in-users)
2. Email notification "Password Reset Email" must be enabled in the Ultimate Member settings (WP-Admin > Ultimate Member > Settings > Email - "Password Reset Email" notification)
3. The Ultimate Member settings must have "Require Strong Passwords" enabled and set to "Password maximum length" (Default setting example - 30 characters)
   (WP-Admin > Ultimate Member > Settings > General > Users - Require Strong Passwords)

Steps to reproduce the test case:

1. Go to the site and log in as a user > Go to the "Password Reset" page
2. In the "Username or Email Address*:" field, enter the username or email address of the user > Click on the "Get new password" button.
3. Check the mailbox of the user whose username or email address was specified when resetting the password > Open the received letter (Mail:Reset your password) and confirm the password reset by clicking on the "Reset your password" button.
4. On the "Password Reset" page, enter a new password that contains more characters than specified in the "Password maximum length" setting in the "New Password*:","Confirm Password*:" fields > Click on the "Save Password" button - check that notice is displayed under the "New Password*:" field.

Expected result:

 - The user is successfully authorized on the site.
 - After clicking on the "Get new password" button on the "Password Reset" page, a notice is displayed:
 >"If an account matching the provided details exists, we will send a password reset link. Check your email for the confirmation link, then visit the login page."
 - After entering the username or email address in the "Username or Email Address*:" field and clicking on the "Get new password" button, an email is sent to the user (Mail:Reset your password)
 - After clicking on the "Reset your password" button in the received email, the user is redirected to the "Password Reset" page.
 - The "Password Reset" page displays two fields "New Password*:","Confirm Password*:" and the "Save Password" button, as well as notice:
 >"Enter your new password below and confirm it."
 - After clicking on the "Save Password" button on the "Password Reset" page, a notice is displayed under the "New Password*:" field:
 >"Your password must contain less than ... characters"
 - The password has not been reset because it contains more characters than specified in the "Password maximum length" setting.

[Screencast Using more than the required number of characters for a new password in the password reset form](https://www.dropbox.com/s/3pkxtf1hkgt8ywe/11.%20Using%20more%20than%20the%20required%20number%20of%20characters%20for%20a%20new%20password%20in%20the%20password%20reset%20form.mp4?dl=0)


## Using only lowercase letters for the new password in the password reset form.

Pre-conditions to reproduce the test case:

1. [Visibility of the "Password reset" form on the site for logged in users](#visibility-of-the-password-reset-form-on-the-site-for-logged-in-users)
2. Email notification "Password Reset Email" must be enabled in the Ultimate Member settings (WP-Admin > Ultimate Member > Settings > Email - "Password Reset Email" notification)
3. Use "Require Strong Passwords" must be enabled in the Ultimate Member settings (WP-Admin > Ultimate Member > Settings > General > Users - Require Strong Passwords)

Steps to reproduce the test case:

1. Go to the site and log in as a user > Go to the "Password Reset" page
2. In the "Username or Email Address*:" field, enter the username or email address of the user > Click on the "Get new password" button
3. Check the mailbox of the user whose username or email address was specified when resetting the password > Open the received letter (Mail:Reset your password) and confirm the password reset by clicking on the "Reset your password" button.
4. On the "Password Reset" page, enter a new password that contains only lowercase characters in the "New Password*:","Confirm Password*:" fields > Click on the "Save Password" button - check that notice is displayed under the "New Password*:" field.

Expected result:

 - The user is successfully authorized on the site.
 - After clicking on the "Get new password" button on the "Password Reset" page, a notice is displayed:
 >"If an account matching the provided details exists, we will send a password reset link. Check your email for the confirmation link, then visit the login page."
 - After entering the username or email address in the "Username or Email Address*:" field and clicking on the "Get new password" button, an email is sent to the user (Mail:Reset your password)
 - After clicking on the "Reset your password" button in the received email, the user is redirected to the "Password Reset" page.
 - The "Password Reset" page displays two fields "New Password*:","Confirm Password*:" and the "Save Password" button, as well as notice:
 >"Enter your new password below and confirm it."
 - After clicking on the "Save Password" button on the "Password Reset" page, a notice is displayed under the "New Password*:" field:
 >"Your password must contain at least one lowercase letter, one capital letter and one number"
 - The password was not reset because it contains only lowercase letters and does not meet the requirement of the enabled setting "Require Strong Passwords".

[Screencast Using only lowercase letters for the new password in the password reset form](https://www.dropbox.com/s/tvdzfoggyrzcyq9/12.%20Using%20only%20lowercase%20letters%20for%20the%20new%20password%20in%20the%20password%20reset%20form.mp4?dl=0)


## Leave the password confirmation field blank in the password reset form.

Pre-conditions to reproduce the test case:

1. [Visibility of the "Password reset" form on the site for logged in users](#visibility-of-the-password-reset-form-on-the-site-for-logged-in-users)
2. Email notification "Password Reset Email" must be enabled in the Ultimate Member settings (WP-Admin > Ultimate Member > Settings > Email - "Password Reset Email" notification)

Steps to reproduce the test case:

1. Go to the site and log in as a user > Go to the "Password Reset" page
2. In the "Username or Email Address*:" field, enter the username or email address of the user > Click on the "Get new password" button
3. Check the mailbox of the user whose username or email address was specified when resetting the password > Open the received letter (Mail:Reset your password) and confirm the password reset by clicking on the "Reset your password" button.
4. On the "Password Reset" page, enter a new password in the "New Password*:" field, and leave the "Confirm Password*:" field blank > Click on the "Save Password" button - check that notice is displayed under the "Confirm Password*:" field.

Expected result:

 - The user is successfully authorized on the site.
 - After clicking on the "Get new password" button on the "Password Reset" page, a notice is displayed:
 >"If an account matching the provided details exists, we will send a password reset link. Check your email for the confirmation link, then visit the login page."
 - After entering the username or email address in the "Username or Email Address*:" field and clicking on the "Get new password" button, an email is sent to the user (Mail:Reset your password)
 - After clicking on the "Reset your password" button in the received email, the user is redirected to the "Password Reset" page.
 - The "Password Reset" page displays two fields "New Password*:","Confirm Password*:" and the "Save Password" button, as well as notice:
 >"Enter your new password below and confirm it."
 - After clicking the "Save Password" button on the "Password Reset" page, a notice is displayed under the "Confirm Password*:" field:
 >"Please fill out this field" (Example)
 - If the confirmation field is left blank when saving the password, then the browser will display a message - an example of "Please fill out this field" or display a notice:
 >"You must confirm your new password"
 - The password has not been reset because the new password confirmation field is required by default.

[Screencast Leave the password confirmation field blank in the password reset form](https://www.dropbox.com/s/1gk9mpcrdpz3bqi/13.%20Leave%20the%20password%20confirmation%20field%20blank%20in%20the%20password%20reset%20form.mp4?dl=0)


## Using mismatched passwords for fields on the password reset form.

Pre-conditions to reproduce the test case:

1. [Visibility of the "Password reset" form on the site for logged in users](#visibility-of-the-password-reset-form-on-the-site-for-logged-in-users)
2. Email notification "Password Reset Email" must be enabled in the Ultimate Member settings (WP-Admin > Ultimate Member > Settings > Email - "Password Reset Email" notification)

Steps to reproduce the test case:

1. Go to the site and log in as a user > Go to the "Password Reset" page
2. In the "Username or Email Address*:" field, enter the username or email address of the user > Click on the "Get new password" button
3. Check the mailbox of the user whose username or email address was specified when resetting the password > Open the received letter (Mail:Reset your password) and confirm the password reset by clicking on the "Reset your password" button.
4. On the "Password Reset" page, enter a new password in the "New Password*:" field, and in the "Confirm Password*:" field, enter a different password > Click on the "Save Password" button - check that notice is displayed.

Expected result:

 - The user is successfully authorized on the site.
 - After clicking on the "Get new password" button on the "Password Reset" page, a notice is displayed:
 >"If an account matching the provided details exists, we will send a password reset link. Check your email for the confirmation link, then visit the login page."
 - After entering the username or email address in the "Username or Email Address*:" field and clicking on the "Get new password" button, an email is sent to the user (Mail:Reset your password)
 - After clicking on the "Reset your password" button in the received email, the user is redirected to the "Password Reset" page.
 - The "Password Reset" page displays two fields "New Password*:","Confirm Password*:" and the "Save Password" button, as well as notice:
 >"Enter your new password below and confirm it."
 - After clicking the "Save Password" button on the "Password Reset" page, a notice is displayed under the "Confirm Password*:" field:
 >"Your passwords do not match"
 - The password was not reset because different passwords were entered in the "New Password" and "Confirm Password" fields when created. The fields must contain the same password.

[Screencast Using mismatched passwords for fields on the password reset form](https://www.dropbox.com/s/i35hnlw01wspype/14.%20Using%20mismatched%20passwords%20for%20fields%20on%20the%20password%20reset%20form.mp4?dl=0)


## Leave the fields for creating a new password in the password reset form blank.

Pre-conditions to reproduce the test case:

1. [Visibility of the "Password reset" form on the site for logged in users](#visibility-of-the-password-reset-form-on-the-site-for-logged-in-users)
2. Email notification "Password Reset Email" must be enabled in the Ultimate Member settings (WP-Admin > Ultimate Member > Settings > Email - "Password Reset Email" notification)

Steps to reproduce the test case:

1. Go to the site and log in as a user > Go to the "Password Reset" page
2. In the "Username or Email Address*:" field, enter the username or email address of the user > Click on the "Get new password" button
3. Check the mailbox of the user whose username or email address was specified when resetting the password > Open the received letter (Mail:Reset your password) and confirm the password reset by clicking on the "Reset your password" button.
4. On the "Password Reset" page, leave the fields "New Password*:","Confirm Password*:" blank > Click on the "Save Password" button - check that notice is displayed.

Expected result:

 - The user is successfully authorized on the site.
 - After clicking on the "Get new password" button on the "Password Reset" page, a notice is displayed:
 >"If an account matching the provided details exists, we will send a password reset link. Check your email for the confirmation link, then visit the login page."
 - After entering the username or email address in the "Username or Email Address*:" field and clicking on the "Get new password" button, an email is sent to the user (Mail:Reset your password)
 - After clicking on the "Reset your password" button in the received email, the user is redirected to the "Password Reset" page.
 - The "Password Reset" page displays two fields "New Password*:","Confirm Password*:" and the "Save Password" button, as well as notice:
 >"Enter your new password below and confirm it."
 - After clicking on the "Save Password" button on the "Password Reset" page, a notice is displayed under the "New Password*:" field:
 >"Please fill out this field" (Example)
 - If the fields are not filled out when saving the password, then the browser will display a message - an example of "Please fill out this field".
 - The password has not been reset because the fields for creating a new password are required by default.

[Screencast Leave the fields for creating a new password in the password reset form blank](https://www.dropbox.com/s/22ir0ujodgb4bfu/15.%20Leave%20the%20fields%20for%20creating%20a%20new%20password%20in%20the%20password%20reset%20form%20blank.mp4?dl=0)


## Set a password reset limit.

Pre-conditions to reproduce the test case:

1. [Visibility of the "Password reset" form on the site for logged in users](#visibility-of-the-password-reset-form-on-the-site-for-logged-in-users)
2. Email notification "Password Reset Email" must be enabled in the Ultimate Member settings (WP-Admin > Ultimate Member > Settings > Email - "Password Reset Email" notification)

Steps to reproduce the test case:

1. As admin go to wp-admin > Settings > Access > Other > Check the "Password reset limit" checkbox
2. In the "Enter password reset limit" setting field, specify the maximum limit for resetting the password - "1" > Click "Save Changes" button.
3. Go to the site and log in as a user > Go to the "Password Reset" page
4. In the "Username or Email Address*:" field, enter the username or email address of the user > Click on the "Get new password" button.
5. Go back to the "Password Reset" page.
6. In the field "Username or Email Address*:" enter the username or email address of the same user > Click on the "Get new password" button - check that notice is displayed.
7. Go to the site as a guest > Go to the "Password Reset" page
8. In the field "Username or Email Address*:" enter the username or email address of the same user > Click on the "Get new password" button - check that notice is displayed.

Expected result:

 - After clicking on the "Save Changes" button at the top of the "Ultimate Member - Settings" page, a notice is displayed:
 >"Settings have been saved successfully."
 - The user is successfully authorized on the site.
 - After clicking on the "Get new password" button on the "Password Reset" page, a notice is displayed:
 >"If an account matching the provided details exists, we will send a password reset link. Check your email for the confirmation link, then visit the login page."
 - After clicking the "Get new password" button again on the "Password Reset" page, a notice is displayed under the "Username or Email Address*:" field:
 >"You have reached the limit for requesting password change for this user already. Contact support if you cannot open the email."
 - For a specific user, you cannot send more requests than the maximum limit set in the "Enter password reset limit" setting.
 - The user is blocked from resetting the password for the next 12 hours if the maximum password reset limit is exceeded.

[Screencast Set a password reset limit](https://www.dropbox.com/s/d1i780aqrg8pb53/16.%20Set%20a%20password%20reset%20limit.mp4?dl=0)


