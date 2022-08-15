# Ultimate Member

## Count users status in wp-admin

This document contains a checklist to do test cases to ensure that the correct user count is displayed after user status changes in wp-admin.

### Contents

 - Count after click "Clear cache" button
 - Count after click "Clear user statuses cache" button  
 
 - 1 Awaiting E-mail Confirmation user - Approved in wp-admin ("Awaiting E-mail Confirmation" status changed to "Approved" status)
 - 1 Awaiting E-mail Confirmation user - Rejected in wp-admin ("Awaiting E-mail Confirmation" status changed to "Rejected" status)
 - 1 Awaiting E-mail Confirmation user - Put as Pending Review in wp-admin ("Awaiting E-mail Confirmation" status changed to "Pending Review" status)
 - 1 Awaiting E-mail Confirmation user - Resend activation E-mail in wp-admin ("Awaiting E-mail Confirmation" status has not changed)
 - 1 Awaiting E-mail Confirmation user - Deactivate in wp-admin ("Awaiting E-mail Confirmation" status changed to "Inactive" status)
 - 1 Awaiting E-mail Confirmation user - Reactivate in wp-admin ("Awaiting E-mail Confirmation" status changed to "Approved" status)
 - 1 Awaiting E-mail Confirmation user - Delete in wp-admin (The count of users has changed)  

 - Multiple Awaiting E-mail Confirmation users - Approved in wp-admin ("Awaiting E-mail Confirmation" status changed to "Approved" status)
 - Multiple Awaiting E-mail Confirmation users - Rejected in wp-admin ("Awaiting E-mail Confirmation" status changed to "Rejected" status)
 - Multiple Awaiting E-mail Confirmation users - Put as Pending Review in wp-admin ("Awaiting E-mail Confirmation" status changed to "Pending Review" status)
 - Multiple Awaiting E-mail Confirmation users - Resend activation E-mail in wp-admin ("Awaiting E-mail Confirmation" status has not changed)
 - Multiple Awaiting E-mail Confirmation users - Deactivate in wp-admin ("Awaiting E-mail Confirmation" status changed to "Inactive" status)
 - Multiple Awaiting E-mail Confirmation users - Reactivate in wp-admin ("Awaiting E-mail Confirmation" status changed to "Approved" status)
 - Multiple Awaiting E-mail Confirmation users - Delete in wp-admin (The count of users has changed)

 - 1 Approved user - Approved in wp-admin ("Approved" status has not changed)
 - 1 Approved user - Rejected in wp-admin ("Approved" status changed to "Rejected" status)
 - 1 Approved user - Put as Pending Review in wp-admin ("Approved" status changed to "Pending Review" status)
 - 1 Approved user - Resend activation E-mail in wp-admin ("Approved" status changed to "Awaiting E-mail Confirmation" status)
 - 1 Approved user - Deactivate in wp-admin ("Approved" status changed to "Inactive" status)
 - 1 Approved user - Reactivate in wp-admin ("Approved" status has not changed) 
 - 1 Approved user - Delete in wp-admin (The count of users has changed)

 - Multiple Approved users - Approved in wp-admin ("Approved" status has not changed)
 - Multiple Approved users - Rejected in wp-admin ("Approved" status changed to "Rejected" status)
 - Multiple Approved users - Put as Pending Review in wp-admin ("Approved" status changed to "Pending Review" status)
 - Multiple Approved users - Resend activation E-mail in wp-admin ("Approved" status changed to "Awaiting E-mail Confirmation" status)
 - Multiple Approved users - Deactivate in wp-admin ("Approved" status changed to "Inactive" status)
 - Multiple Approved users - Reactivate in wp-admin ("Approved" status has not changed)
 - Multiple Approved users - Delete in wp-admin (The count of users has changed)

 - 1 Pending review user - Approved in wp-admin ("Pending Review" status changed to "Approved" status)
 - 1 Pending review user - Rejected in wp-admin ("Pending Review" status changed to "Rejected" status)
 - 1 Pending review user - Put as Pending Review in wp-admin ("Pending Review" status has not changed)
 - 1 Pending review user - Resend activation E-mail in wp-admin ("Pending Review" status changed to "Awaiting E-mail Confirmation" status)
 - 1 Pending review user - Deactivate in wp-admin ("Pending Review" status changed to "Inactive" status)
 - 1 Pending review user - Reactivate in wp-admin ("Pending Review" status changed to "Approved" status)
 - 1 Pending review user - Delete in wp-admin (The count of users has changed)

 - Multiple Pending review users - Approved in wp-admin ("Pending Review" status changed to "Approved" status)
 - Multiple Pending review users - Rejected in wp-admin ("Pending Review" status changed to "Rejected" status)
 - Multiple Pending review users - Put as Pending Review in wp-admin ("Pending Review" status has not changed)
 - Multiple Pending review users - Resend activation E-mail in wp-admin ("Pending Review" status changed to "Awaiting E-mail Confirmation" status)
 - Multiple Pending review users - Deactivate in wp-admin ("Pending Review" status changed to "Inactive" status)
 - Multiple Pending review users - Reactivate in wp-admin ("Pending Review" status changed to "Approved" status)
 - Multiple Pending review users - Delete in wp-admin (The count of users has changed)

 - 1 Rejected user - Approved in wp-admin ("Rejected" status changed to "Approved" status)
 - 1 Rejected user - Rejected in wp-admin ("Rejected" status has not changed)
 - 1 Rejected user - Put as Pending Review in wp-admin ("Rejected" status changed to "Pending Review" status)
 - 1 Rejected user - Resend activation E-mail in wp-admin ("Rejected" status changed to "Awaiting E-mail Confirmation" status)
 - 1 Rejected user - Deactivate in wp-admin ("Rejected" status changed to "Inactive" status)
 - 1 Rejected user - Reactivate in wp-admin ("Rejected" status changed to "Approved" status)
 - 1 Rejected user - Delete in wp-admin (The count of users has changed)

 - Multiple Rejected users - Approved in wp-admin ("Rejected" status changed to "Approved" status)
 - Multiple Rejected users - Rejected in wp-admin ("Rejected" status has not changed)
 - Multiple Rejected users - Put as Pending Review in wp-admin ("Rejected" status changed to "Pending Review" status)
 - Multiple Rejected users - Resend activation E-mail in wp-admin ("Rejected" status changed to "Awaiting E-mail Confirmation" status)
 - Multiple Rejected users - Deactivate in wp-admin ("Rejected" status changed to "Inactive" status)
 - Multiple Rejected users - Reactivate in wp-admin ("Rejected" status changed to "Approved" status)
 - Multiple Rejected users - Delete in wp-admin (The count of users has changed)

 - 1 Inactive user - Approved in wp-admin ("Inactive" status changed to "Approved" status)
 - 1 Inactive user - Rejected in wp-admin ("Inactive" status changed to "Rejected" status)
 - 1 Inactive user - Put as Pending Review in wp-admin ("Inactive" status changed to "Pending Review" status)
 - 1 Inactive user - Resend activation E-mail in wp-admin ("Inactive" status changed to "Awaiting E-mail Confirmation" status)
 - 1 Inactive user - Deactivate in wp-admin ("Inactive" status has not changed)
 - 1 Inactive user - Reactivate in wp-admin ("Inactive" status changed to "Approved" status)
 - 1 Inactive user - Delete in wp-admin (The count of users has changed)

 - Multiple Inactive users - Approved in wp-admin ("Inactive" status changed to "Approved" status)
 - Multiple Inactive users - Rejected in wp-admin ("Inactive" status changed to "Rejected" status)
 - Multiple Inactive users - Put as Pending Review in wp-admin ("Inactive" status changed to "Pending Review" status)
 - Multiple Inactive users - Resend activation E-mail in wp-admin ("Inactive" status changed to "Awaiting E-mail Confirmation" status)
 - Multiple Inactive users - Deactivate in wp-admin ("Inactive" status has not changed)
 - Multiple Inactive users - Reactivate in wp-admin ("Inactive" status changed to "Approved" status)
 - Multiple Inactive users - Delete in wp-admin (The count of users has changed)

 - Сreating approved user in wp-admin (The count of users has changed)
 - Edit approved user in wp-admin (The count of users has not changed)
 - Registration of a new user on the site with the status Approved (The count of users has changed)
 - Registration of a new user on the site with status Awaiting E-mail Confirmation (The count of users with "Awaiting E-mail Confirmation" status has changed)
 - Registration of a new user on the site with the status of Pending Review (The count of users with "Pending Review" status has changed)
 - Deleting a user account on the site (The count of users has changed)

 - Make a user verification request on the site (The count of users with "Request Verification" status has changed) [Activation of the Ultimate Member - Verified Users extension is required](https://docs.ultimatemember.com/article/184-verified-users-setup)
 - Cancel the user verification request on the site (The count of users with "Request Verification" status has changed) [Activation of the Ultimate Member - Verified Users extension is required](https://docs.ultimatemember.com/article/184-verified-users-setup)
 - Reject verification request in wp-admin (The count of users with "Request Verification" status has changed) [Activation of the Ultimate Member - Verified Users extension is required](https://docs.ultimatemember.com/article/184-verified-users-setup)
 - Approve verification request in wp-admin (The count of users with "Request Verification" status has changed) [Activation of the Ultimate Member - Verified Users extension is required](https://docs.ultimatemember.com/article/184-verified-users-setup)
 - Mark accounts as verified in wp-admin (The count of users with "Request Verification" status has changed) [Activation of the Ultimate Member - Verified Users extension is required](https://docs.ultimatemember.com/article/184-verified-users-setup)
 - Mark accounts as unverified in wp-admin (The count of users with "Request Verification" status has changed) [Activation of the Ultimate Member - Verified Users extension is required](https://docs.ultimatemember.com/article/184-verified-users-setup)
