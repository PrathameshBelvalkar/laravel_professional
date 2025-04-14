<!DOCTYPE html>
<html lang="en" dir="ltr" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
   <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width">
      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <meta name="x-apple-disable-message-reformatting">
      <title>Silo Mail</title>
      <link href="https://fonts.googleapis.com/css?family=Roboto:400,600" rel="stylesheet" type="text/css">
      <style>
         html,
         body {
         margin: 0 auto !important;
         padding: 0 !important;
         height: 100% !important;
         width: 100% !important;
         font-family: 'Roboto', sans-serif !important;
         font-size: 14px;
         margin-bottom: 10px;
         line-height: 24px;
         color: #8094ae;
         font-weight: 400;
         }
         * {
         -ms-text-size-adjust: 100%;
         -webkit-text-size-adjust: 100%;
         margin: 0;
         padding: 0;
         }
         table,
         td {
         mso-table-lspace: 0pt !important;
         mso-table-rspace: 0pt !important;
         }
         table {
         border-spacing: 0 !important;
         border-collapse: collapse !important;
         table-layout: fixed !important;
         margin: 0 auto !important;
         }
         table table table {
         table-layout: auto;
         }
         a {
         text-decoration: none;
         }
         img {
         -ms-interpolation-mode: bicubic;
         }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            font-size: 16px;
            color: #fff!important;
            background-color: #e14954; 
            border-radius: 5px;
            text-decoration: none;
        }
        .btn:hover {
            background-color: #c43c47; 
        }
      </style>
   </head>
   <body width="100%" style="margin: 0; padding: 0 !important; mso-line-height-rule: exactly; background-color: #f5f6fa;">
      <center style="width: 100%; background-color: #f5f6fa;">
         <table width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="#f5f6fa">
            <tr>
               <td style="padding: 40px 0;">
                  <table style="width:100%;max-width:620px;margin:0 auto;">
                     <tbody>
                        <tr>
                           <td style="text-align: center; padding-bottom:25px">
                              <a href="#"><img style="min-height: 60px !important;max-height: 60px !important;" src="{{ $mailData['logo'] }}" alt="logo"></a>
                              <!-- <p class="email-title">Silo Mail</p> -->
                              <h3>Hello,</h3>
                              <p><strong>{{ $mailData['sender'] }}</strong> invites you to join the conversation on Silomail. Click below to sign up:</p><br>
                                
                               <a href="{{ $mailData['link'] }}" class="btn">Join SiloMail</a>
                           </td>
                        </tr>
                     </tbody>
                  </table>
                  <table
                     style="width:100%;max-width:620px;margin:0 auto;background-color:#ffffff;text-align: center;">
                     <tbody>
                        <tr>
                           <td style="padding: 30px 30px 20px">
                              
                              <div class="tab-content">
                              {!! $mailData['message'] !!}
                              </div>
                              
                           </td>
                        </tr>
                     </tbody>
                  </table>
                  <table style="width:100%;max-width:620px;margin:0 auto;">
                     <tbody>
                        <tr>
                           <td style="text-align: center; padding:25px 20px 0;">
                              <p style="font-size: 13px;">2024©SiloCloud Inc & Companies <br> Evolution of
                                 Communications
                              </p>
                              <ul style="margin: 10px -4px 0;padding: 0;">
                                 <li style="display: inline-block; list-style: none;"><a
                                    style="display: inline-block; height: 30px; padding: 4px; width:30px;border-radius: 50%;"
                                    href="{{ $mailData['facebook_link']}}"><img style="width: 30px" src="{{ $mailData['facebook']}}"
                                    alt="Facebook Logo"></a></li>
                                 <li style="display: inline-block; list-style: none;"><a
                                    style="display: inline-block; height: 30px; padding: 4px; width:30px;border-radius: 50%;"
                                    href="{{ $mailData['instagram_link']}}]"><img style="width: 30px" src="{{ $mailData['instagram']}}"
                                    alt="Instagram Logo"></a></li>
                                 <li style="display: inline-block; list-style: none;"><a
                                    style="display: inline-block; height: 30px; padding: 4px; width:30px;border-radius: 50%;"
                                    href="{{ $mailData['pinterest_link']}}"><img style="width: 30px" src="{{ $mailData['pinterest'] }}"
                                    alt="Pinterest Logo"></a></li>
                                 <li style="display: inline-block; list-style: none;"><a
                                    style="display: inline-block; height: 30px; padding: 4px; width:30px;border-radius: 50%;"
                                    href="{{ $mailData['twitter_link']}}"><img style="width: 30px;" src="{{ $mailData['twitter']}}"
                                    alt="Twitter Logo"></a></li>
                              </ul>
                              <p style="padding-top: 15px; font-size: 12px;">This email was sent by <strong>{{ $mailData['sender'] }}</strong> as a registered member of <a href="https://silocloud.io"><strong>silocloud.io</strong></a>.
                              </p>
                           </td>
                        </tr>
                     </tbody>
                  </table>
               </td>
            </tr>
         </table>
      </center>
   </body>
</html>