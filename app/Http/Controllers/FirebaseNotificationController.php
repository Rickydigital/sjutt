<?php

namespace App\Http\Controllers;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Http\Request;

class FirebaseNotificationController extends Controller
{
    public function sendNotification(Request $request)
    {
        $firebase = (new Factory)
            ->withCredentials(storage_path('app/firebase-credentials.json'))
            ->createMessaging();

        // Retrieve the FCM token from the request
        $fcmToken = $request->input('fcm_token');
        
        // Define the notification
        $notification = Notification::create(
            $request->input('title'),
            $request->input('body')
        );

        // Send the message
        $message = CloudMessage::withTarget('token', $fcmToken)
            ->withNotification($notification);

        try {
            $firebase->send($message);
            return response()->json(['success' => true, 'message' => 'Notification sent successfully!']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to send notification', 'error' => $e->getMessage()]);
        }
    }
}
