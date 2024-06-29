<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use Paystack;

class UserController extends Controller
{
    //validate student using matric number
    public function validateStudent(Request $request)
    {
        try {
            $request->validate([
                'matric_no' => 'required|string'
            ]);

            $student = Student::where('matric_no', $request->matric_no)->first();

            if (!$student) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Student not found'
                ], 404);
            }

            // check if student has paid
            if ($student->is_paid) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Student has paid sir',
                    'data' => $student
                ], 409);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Student found',
                'data' => $student
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    //verify student payment
    public function verifyPayment(Request $request)
    {
        try {
            $request->validate([
                'matric_no' => 'required|string'
            ]);

            $student = Student::where('matric_no', $request->matric_no)->first();

            if (!$student) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Student not found',
                    
                ], 404);
            }

            

            if ($student->is_paid) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Student has paid',
                    'data' => $student
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Student has not paid',
                'data' => $student
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }




    //initialize payment
    public function initialize_payment(Request $request)
    {

       


        try {

            $request->validate([
                'matric_no' => 'required|string'
            ]);

            $user = Student::where('matric_no', $request->matric_no)->first();




            $reference = Paystack::genTranxRef();

            $amount = 5000;
            
            //save refference to the database
            $user->reference = $reference;
            $user->save();
            


            // prepare data for payment
            $data = [
               
                'email' => $user->email ?? $user->matric_no . '@socfyb.com',
                'amount' => $amount * 100, //paystack expect the anount in kobo
                'reference' => $reference, //unique reference
                'currency' => 'NGN',
                'metadata' => [
                    'matric_no' => $user->matric_no,
                    'name' => $user->name,
                    'department' => $user->department
                ]
                
            ];

           





            //initialize payment
            $payment = Paystack::getAuthorizationUrl($data);



            //get the payment url
            $payment_url = $payment->url;


            return response()->json([
                'status' => true,
                'message' => 'Payment initialized successfully',
                'data' => [
                    'payment_url' => $payment_url,
                    'reference' => $reference
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to initialize payment: ' . $e->getMessage()
            ], 500);
        }
    }


    //paystack callback
    public function paystack_callback()
    {
        try {
            //get the reference
            $paymentDetails = Paystack::getPaymentData();



            // Check if payment was successful
            if ($paymentDetails['data']['status'] === 'success') {
                // Process successful transaction
                // e.g., update the order status, send email confirmation, ennroll the user for the paid course.







                //get the cart







                // redirect to frontend
                $frontendUrl = env('FRONTEND_URL') . "/payment-complete";

                return redirect($frontendUrl);
            } else {
                // Handle failed transaction

                // redirect to frontend
                $frontendUrl = $frontendUrl = env('FRONTEND_URL');

                return redirect($frontendUrl);
            }
        } catch (\Exception $e) {
            $frontendUrl = env('FRONTEND_URL');
            return redirect($frontendUrl);
        }
    }


    //paystack webhook
    public function paystack_webhook(Request $request)
    {
        try {


           


            //get payload
            $payload = json_decode($request->getContent(), true);
            $event = $payload['event'];

            





            // Log the incoming webhook payload
            Log::info('Paystack webhook received: ' . $request->getContent());

            //verify the webhook
            $paystackSecret = env('PAYSTACK_SECRET_KEY');
            $paystackHeader = $request->header('x-paystack-signature');


            // Verify the webhook signature
            $valid = $this->isValidPaystackWebhook($request->getContent(), $paystackHeader, $paystackSecret);

            // Log the verification status
            Log::info('Paystack webhook verification status: ' . $valid);

            // If the webhook is not valid, return response
            if (!$valid) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid signature'
                ], 401);
            }

            // return response to our friend, Paystack
            http_response_code(200);

            // Handle different types of events
            switch ($event) {
                case 'charge.success':
                    // Handle charge success
                    $this->handlePaymentIntentSucceeded($payload['data']);
                    break;
                case 'transfer.success':
                    // Handle transfer success
                    $this->handlePaymentIntentSucceeded($payload['data']);
                    break;
                case 'charge.failed':
                    // Handle charge failed
                    $this->handlePaymentIntentFailed($payload['data']);
                    break;
                default:
                    // Unexpected event type
                    return response()->json([
                        'status' => false,
                        'message' => 'Unexpected event type'
                    ], 400);
            }

            // Log success

            return response()->json([
                'status' => true,
                'message' => 'Webhook received successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to verify payment: ' . $e->getMessage()
            ], 500);
        }
    }


    //verify signature
    private function isValidPaystackWebhook($payload, $signature, $secret)
    {

        $computedSignature = hash_hmac('sha512', $payload, $secret);
        return $computedSignature === $signature;
        // return hash_equals($hash, $signature);
    }


    //handle payment intent success
    protected function handlePaymentIntentSucceeded($paymentIntent)
    {

        $reference = $paymentIntent['reference'];
        $amount = $paymentIntent['amount'] / 100;

        $student = Student::where('reference', $reference)->first();

        // Business logic for handling successful payments
        // Update the order status in your database
        if ($student) {
            $student->is_paid = true;
            $student->save();
        }



        // Log success
        Log::info('PaymentIntent succeeded: ' . json_encode($paymentIntent));


    }

    //handle payment intent failed
    protected function handlePaymentIntentFailed($paymentIntent)
    {
        // Business logic for handling failed payments
        Log::error('PaymentIntent failed: ' . $paymentIntent->id);

        //send email to user later
    }
}
