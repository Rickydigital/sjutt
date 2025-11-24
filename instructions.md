API DOCUMENTATION FOR BACKEND DEVELOPER

Endpoint for updating the FCM token:

- Route: POST /api/update-fcm-token
- Description: Updates the Firebase Cloud Messaging (FCM) token for a user. This is crucial for sending push notifications. The request must be authenticated with a bearer token.
- Request Body (JSON):
  {
    "user_id": 123,
    "fcm_token": "the_new_fcm_token_string"
  }
- Success Response (200 OK):
  {
    "success": true,
    "message": "FCM token updated successfully."
  }
- Error Response (e.g., 401 Unauthorized, 404 Not Found, 500 Server Error):
  {
    "success": false,
    "message": "A descriptive error message."
  }
*/