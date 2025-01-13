<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">

    <style>

@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Belanosima:wght@400;600;700&family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Geologica:wght@100..900&family=Lilita+One&family=Montaga&family=Montserrat+Alternates:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Montserrat:ital,wght@0,100..900;1,100..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Rubik:ital,wght@0,300..900;1,300..900&family=Sora:wght@100..800&family=Varela&display=swap');

        body,
        html {
            font-family: 'Montserrat', sans-serif;
            font-size: 0.9em;
        }

        .container {
            min-width: 320px;
            width: 80%;
            background: white;
            margin:20px auto;
            display: flex;
            flex-direction: column;
            align-content: center;
            /* border-radius: 10px; */
        }

        .content {
            padding: 20px;
            border: 1px solid transparent;
        }

        .head {
            text-align: center;
            display: flex;
            flex-direction: column;
        }

        .head div {
            font: bold;
            font-size: 1.5em;
            font-weight: bolder;
        }

        .head span {
            color: grey;
        }

        .table-container {
            overflow-x: auto;
            border: 2px solid #cecece;
            white-space: nowrap;
            overflow-x: scroll;
            width: 100%;
            max-width: 1024px;
            margin: 50px 0 0 0;
            font-size: 0.8em;
        }

        table {
            border-collapse: collapse;
            border-spacing: 0;
            width: 100%;
            border: 1px solid transparent;
            background-color: white;
            overflow-x: auto;
        }

        th,
        td {
            text-align: left;
            padding: 10px;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2
        }

        .details {
            width:98%;
            border: 1px solid transparent;
            border-radius: 5px;
            background: #f2f2f2;
            padding: 10px;
            margin:5px auto;
            /* padding:15px; */
        }

        .details-box {
            width: 100%;
            display: flex;
            justify-content: space-between;
            border: 1px solid transparent;
            /* padding: 10px; */
        }

        .firstbox {
            width:45%;
            padding: 2px;
            border: 1px solid transparent;
            text-align: start;
            font-weight: bold;
        }

        .secondbox {
            width:45%;
            padding:2px;
            border: 1px solid transparent;

            left: 20px;
        }
    </style>
</head>

<body>

    <div style="min-width:320px;overflow:auto;line-height:2;background-color:#cecece; ">

        <div class="container" style="display: flex; flex-direction:column;  align-content: center; ">
            <div style="border-bottom:none;">
                <a href="https://kobosquare.com" style="font-size:1.4em;color: #00466a;text-decoration:none;font-weight:600">
                    {{-- <img src="{{ asset('assets/Email banner.png') }}" style="width:100%; height:80px"> --}}
                    <img src="https://api.kobosquare.com/emails/Emailbanner.png" style="width:20%">
                </a>
            </div>

            <div class="head">
                <div>Payment Succesful</div>
                <span>Order No: 1234565i6t</span>
                <div>Kobo Eats</div>
            </div>


            <div class="content">

                <div style="border: 1px solid transparent;color:black;">
                    <div style="font-size:1.1em; font-weight:bold;">Olarinde,</div>
                    <div>Your payment was successful, you transaction is listed below</div>
                </div>
{{--
                    <div class="details-box">
                        <div class="firstbox">Delivery Adresss</div>
                        <div class="secondbox">Lorem ipsum dolor sit amet consectetur adipisicing elit. Architecto beatae repellat delectus ut, nobis tempora, </div>
                    </div> --}}

                    <div class="details">


                        <div class="details-box">
                            <div class="firstbox">Merchant Name </div>
                            <div class="secondbox">Codefixbug limited </div>
                        </div>


                        <div class="details-box">
                            <div class="firstbox">Amount </div>
                            <div class="secondbox">NGN 1,500</div>
                        </div>


                        <div class="details-box">
                            <div class="firstbox">Payment Method</div>
                            <div class="secondbox">Wallet</div>
                        </div>

                        <div class="details-box">
                            <div class="firstbox">Payment Status </div>
                            <div class="secondbox">Codefixbug limited </div>
                        </div>

                        <div class="details-box">
                            <div class="firstbox">Delivery Type</div>
                            <div class="secondbox">Codefixbug limited </div>
                        </div>


                        <div class="details-box">
                            <div class="firstbox">Transaction Time</div>
                            <div class="secondbox">Codefixbug limited </div>
                        </div>


                        <div class="details-box">
                            <div class="firstbox">Transaction Date</div>
                            <div class="secondbox">Codefixbug limited </div>
                        </div>


                        <div class="details-box">
                            <div class="firstbox">Order Type</div>
                            <div class="secondbox">Codefixbug limited </div>
                        </div>

                    </div>

                    <p>Thanks for your patronage,</p>
                    <p><strong>Kobosquare Team</strong></p>
                    <p>For any feedback or inquiries, get in touch with us at <a href="mailto:support@kobosquare.com">support@kobosquare.com</a></p>
                    <hr style="border:none;border-top:1px solid #eee" />
                    <div style="float:right;padding:8px 0;color:#aaa;font-size:0.8em;line-height:1;font-weight:300">
                        <p>Kobosquare</p>
                    </div>

                </div>
            </div>

        </div>
    </div>
</body>

</html>
