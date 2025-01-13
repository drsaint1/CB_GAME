<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>

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
            margin: 20px auto;
            display: table;
            border-collapse: collapse;
        }

        .content {
            padding: 20px;
            border: 1px solid transparent
        }

        .head {
            text-align: center;
            display: table;
            width: 100%;
        }

        .head div {
            font-size: 1.5em;
            font-weight: bolder;
        }

        .head span {
            color: grey;
        }

        tr{
        margin-top:"20px";
        }

        .details {
            width: 98%;
            border: 1px solid transparent;
            border-radius: 5px;
            background: #f2f2f2;
            padding: 10px;
            margin: 5px auto;
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
                    <img src="https://api.kobosquare.com/emails/Emailbanner.png" style="width:100%; height:200px">
                </a>
            </td>
        </tr>

        <tr>
            <td class="head">
                <div>{{$subject}}</div>
                <span>Order No: {{$orderno}} </span>
                <div>{{$type}}</div>
            </td>
        </tr>

        <tr>
            <td class="content">
                <div style="border: 1px solid transparent; color: black;">
                    <div>Hi {{$name}},</div>
                    <div>{{$msg}}</div>

                    <div class="details-box">
                        <div class="firstbox">Delivery Address</div>
                        <div class="secondbox">{{ $delivery_address ?? 'Not available' }}</div>
                    </div>

                    <div class="details">
                        <div class="details-box">
                            <div class="firstbox">Merchant Name </div>
                            <div class="secondbox">{{$merchantName}}</div>
                        </div>

                        <div class="details-box">
                            <div class="firstbox">Amount </div>
                            <div class="secondbox">NGN {{$total}}</div>
                        </div>

                        <div class="details-box">
                            <div class="firstbox">Payment Method</div>
                            <div class="secondbox">{{$method}}</div>
                        </div>

                        <div class="details-box">
                            <div class="firstbox">Payment Status </div>
                            <div class="secondbox">{{$paymentStatus}}</div>
                        </div>

                        <div class="details-box">
                            <div class="firstbox">Transaction Date</div>
                            <div class="secondbox">{{$currentDate}}</div>
                        </div>
                    </div>

                    <p>Thanks for your patronage,</p>
                    <p>Kobosquare Team</p>
                    <p style="margin-top:20px;">If you have any issues or complaints concerning any transaction, kindly send us an email at  <a href="mailto:support@kobosquare.com">support@kobosquare.com</a></p>

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
                    <div style="padding: 12px 0; color: black; font-size: 0.9em; line-height: 1; font-weight: 300">
                        <p>Kobosquare</p>
                    </div>
                </div>
            </td>
        </tr>
    </table>

</body>


</html>

