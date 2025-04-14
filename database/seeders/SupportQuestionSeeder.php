<?php

namespace Database\Seeders;

use App\Models\HelpQuestionAndAnswer;
use App\Models\SupportQuestion;
use Illuminate\Database\Seeder;

class SupportQuestionSeeder extends Seeder
{

    public function run(): void
    {

        $questions = [
            [
                'id' => 1,
                'category_id' => '1',
                'title' => 'I forgot my password. How can I reset it?',
                'slug' => 'i-forgot-my-password-how-can-i-reset-it?',
                'answer' => 'To reset your password, you can use the "Forgot Password" feature on the login page. Click on the "Forgot Password" link, and then enter your email address associated with your account. You will receive an email with instructions on how to reset your password.',

            ],
            [
                'id' => 2,
                'category_id' => '2',
                'title' => 'How can I reset my password?',
                'answer' => '1.Go to the login page of our website.
                2.Click on the "Forgot Password?" link below the login form.
                3.Enter your email address associated with your account.
                4.You will receive an email with instructions on how to reset your password.
                5.Follow the instructions in the email to create a new password for your account.',
                'slug' => 'How-can-I-reset-my-password',

            ],
            [
                'id' => 3,
                'category_id' => '3',
                'title' => 'can you check Check Your Internet Connection',
                'answer' => 'Verify that you have a stable internet connection. You can try accessing other websites or apps to see if the issue is specific to our website/app or if it is a broader connectivity problem.',
                'slug' => 'can-you-check-Check-your-internet-connection',

            ],
            [
                'id' => 4,
                'category_id' => '2',
                'title' => 'I am experiencing connectivity issues with the website/app. What should I do?',
                'answer' => 'Connectivity issues can be caused by various factors such as network problems.',
                'slug' => 'i-am-experiencing-connectivity-issues-with-the-website/app-What-should-i-do',

            ],
            [
                'id' => 5,
                'category_id' => '3',
                'title' => 'I am not receiving emails from the website. What could be the issue?',
                'answer' => 'Check your spam or junk email folder to see if the emails are being filtered there. Make sure the email address associated with your account is correct and up to date.',
                'slug' => 'i-am-not-receiving-emails-from-the-website-What-could-be-the-issue',

            ],
            [
                'id' => 6,
                'category_id' => '1',
                'title' => 'Can you Verify Email Address ?',
                'answer' => 'Make sure that the email address associated with your account on our website is correct and up to date. If you have changed your email address recently, update it in your account settings.',
                'slug' => 'can-you-Verify-email-address',

            ],
            [
                'id' => 7,
                'category_id' => '1',
                'title' => 'My ticket is consistently being closed without resolution. What should I do?',
                'answer' => 'Ensure that you provide detailed information about the issue you are facing in your ticket. Include any relevant details, screenshots, or error messages that can help our support team understand the problem better',
                'slug' => 'my-ticket-is-consistently-being-closed-without-resolution-what-should-i-do',

            ],
            [
                'id' => 8,
                'category_id' => '2',
                'title' => 'My account has been suspended. What should I do?',
                'answer' => 'If your account has been suspended, it might be due to a violation of our terms of service or community guidelines. To resolve this issue, please reach out to our support team via the ticket system or email us at support@example.com with your account details and the reason for suspension. Our team will investigate the issue and provide further assistance on how to proceed with reinstating your account if possible. Thank you for your understanding.',
                'slug' => 'my-account-has-been-suspended-what-should-i-do?',

            ],
            [
                'id' => 9,
                'category_id' => '3',
                'title' => 'How can I request changes to my subscription plan?',
                'answer' => ' If you need to make changes to your subscription plan, such as upgrading, downgrading, or cancelling your subscription, you can do so by accessing your account settings on our website. Simply log in to your account, navigate to the subscription or billing section, and you should find options to manage your subscription. If you encounter any difficulties or have specific requests regarding your subscription, you can also reach out to our support team for assistance. We are here to help you with any changes you need to make to your subscription.',
                'slug' => 'how-can-i-request-changes-to-my-subscription-plan',

            ],
            [
                'id' => 10,
                'category_id' => '2',
                'title' => 'Can I stop a ticket from closing after 28 days?',
                'answer' => 'Yes, you can prevent a ticket from closing automatically after 28 days by updating its status or marking it as unresolved. Typically, ticketing systems have options to manually change the status of a ticket. If you need assistance with this, please provide the ticket number or details, and our support team will be happy to help you keep the ticket open as needed.',
                'slug' => 'can-I-stop-a-ticket-from-closing-after-28-days?',
            ],
            [
                'id' => 11,
                'category_id' => '1',
                'title' => 'How can I troubleshoot issues with tickets?',
                'answer' => 'Start by reviewing the details of the ticket, including the description of the issue, any attached files, and the ticket status.',
                'slug' => 'how-can-i-troubleshoot-issues-with-tickets',
            ]
        ];


        foreach ($questions as $question) {
            SupportQuestion::create($question);
        }
    }
}
