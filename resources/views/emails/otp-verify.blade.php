<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
</head>

<div style=" font-family: 'Poppins', sans-serif;min-width:320px;overflow:auto;line-height:2;background-color:#cecece; ">
    <div style="margin:50px auto;width:70%; background:white; border-radius:10px; border:1px solid #cecece; padding:20px;" >
      <div style="border-bottom:none;">
        <a href="https://kobosquare.com" style="font-size:1.4em;color: #00466a;text-decoration:none;font-weight:600">
            <img src="{{ asset('assets/Kobo_mail_footer.png') }}" style="width:20%">
        </a>
      </div>

      <div style="border: 1px solid transparent;">
      <p style="font-size:1.1em; font-weight:bold;">Hi, {{$name}}</p>
      <p style="font-size:2em; font-weight:bold;">Confirm your Identity</p>
      <p>Thank you for choosing Your KoboSquare. Use the following OTP to verify your email. OTP is valid for 15 minutes</p>
      <h2 style="background: #b2ecbe;margin: 0 auto;width:95%;padding: 0 10px;color: black;border-radius: 4px; text-align:center; position: relative;">{{$code}}</h2>
      <p>Thank you,</p>
      <p><strong>Kobosquare Team</strong></p>
      <p>For any feedback or inquiries, get in touch with us at <a href="mailto:support@kobosquare.com">support@kobosquare.com</a></p>
      <hr style="border:none;border-top:1px solid #eee" />
      <div style="float:right;padding:8px 0;color:#aaa;font-size:0.8em;line-height:1;font-weight:300">
        <p>Kobosquare</p>
      </div>

    </div>
    </div>
  </div>
