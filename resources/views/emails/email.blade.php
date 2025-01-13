<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Belanosima:wght@400;600;700&family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Geologica:wght@100..900&family=Lilita+One&family=Montaga&family=Montserrat+Alternates:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&family=Montserrat:ital,wght@0,100..900;1,100..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Rubik:ital,wght@0,300..900;1,300..900&family=Sora:wght@100..800&family=Varela&display=swap');
        body,
        html {
            font-family: 'Montserrat', sans-serif;
            font-size: 0.9em;
            background-color: #cecece;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
            max-width: 600px;
            background: white;
            margin:20px auto;
            display: table;
            border-collapse: collapse;
        }

        .content {
            padding: 20px;
            border: 1px solid transparent;
        }

        tr{
          padding:20px;
          margin-top:20px;
          }

        tr{
          padding:20px;
          margin-top:20px;
          }

        .head {
            text-align: center;
            display: table;
            width: 100%;
            margin-top: 20px;
        }

        .head div {
            font-size: 1.5em;
            font-weight: bolder;
        }

        .head span {
            color: grey;
        }

        .details {
            width: 95%;
            border: 1px solid transparent;
            border-radius: 5px;
            background: #f2f2f2;
            padding: 10px;
            margin-top: 5px;
        }

        .details-box {
            width: 100%;
            display: table;
            border: 1px solid transparent;
        }

        .firstbox,
        .secondbox {
            width: 45%;
            padding: 2px;
            border: 1px solid transparent;
            text-align: start;
            font-weight: bold;
            display: table-cell;
        }

        h2 {
            background: #f2f2f2;
            margin: 0 auto;
            width: 100%;
            padding: 0 10px;
            color: black;
            border-radius: 4px;
            text-align: center;
            position: relative;
        }

        a {
            /* font-size: 1.4em; */
            color: #00466a;
            text-decoration: none;
            font-weight: 600;
        }

        .image{
          width: 100%;
        }
    </style>
</head>

<body>

    <table class="container">
        <tr>
            <td style="padding: 0; border-bottom: none;">
                <a href="https://kobosquare.com">
                    <img src="https://api.kobosquare.com/emails/Emailbanner.png" style="width:100%;  height:200px">
                </a>
            </td>
        </tr>

        <tr style="margin-top:20px">
            <td class="head">
                <div>Otp Verification Code</div>
            </td>
        </tr>

        <tr>
            <td class="content">
                <div style="border: 1px solid transparent; color: black; padding: 10px 15px 0 0">
                    <div style="display: table; width: 100%; justify-content: space-between;">
                        <div> Hi {{ strtolower($name) }},  </div>
                    </div>

                    <div style="margin-top:20px;">Complete your verification!</div>
                    <div style="margin-top:20px">Thank you for choosing Your KoboSquare. Use the following OTP to verify your email. OTP is valid for 15 minutes</div>
                    <h2 style="margin-top:20px;padding:10px;">{{$code}}</h2>

                    <p style="margin-top:20px;">Kobosquare Team</p>
                    <p style="margin-top:20px;">If you have any issues or complaints, kindly send us an email at  <a href="mailto:support@kobosquare.com">support@kobosquare.com</a></p>

                    <div class="image" style="text-align: center; margin-top:25px;">
                        <span class="imageboxa">
                            {{-- <img src="{{ asset('emails/appstore.webp')}}" style="width:20%;"> --}}
                            <img src="https://api.kobosquare.com/emails/appstore.webp" style="width:20%;">
                        </span>
                        <span class="imageboxb">
                            {{-- <img src="{{asset('emails/googleplay.webp') }}" style="width:20%;"> --}}
                            <img src="https://api.kobosquare.com/emails/googleplay.webp" style="width:20%;">
                        </span>

                    </div>

                    <hr style="border: none; border-top: 1px solid #eee" />
                    <div style="padding: 12px 0; color: black; font-size: 0.9em; line-height: 1; font-weight: 300;text-align:center;">
                        <p>Kobosquare</p>
                    </div>
                </div>
            </td>
        </tr>
    </table>

</body>


</html>
