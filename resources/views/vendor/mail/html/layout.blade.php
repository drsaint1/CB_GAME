<!DOCTYPE html>

<html lang="en" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:v="urn:schemas-microsoft-com:vml">

<head>
    <title></title>
    <meta content="text/html; charset=utf-8" http-equiv="Content-Type" />
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <!--[if mso]><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch><o:AllowPNG/></o:OfficeDocumentSettings></xml><![endif]-->
    <!--[if !mso]><!-->
    <link href="https://fonts.googleapis.com/css?family=Bitter" rel="stylesheet" type="text/css" />
    <link href="https://fonts.googleapis.com/css?family=Cabin" rel="stylesheet" type="text/css" />
    <link href="https://fonts.googleapis.com/css?family=Droid+Serif" rel="stylesheet" type="text/css" />
    <link href="https://fonts.googleapis.com/css?family=Permanent+Marker" rel="stylesheet" type="text/css" />
    <link href="https://fonts.googleapis.com/css?family=Oxygen" rel="stylesheet" type="text/css" />
    <link href="https://fonts.googleapis.com/css?family=Oswald" rel="stylesheet" type="text/css" />
    <link href="https://fonts.googleapis.com/css?family=Merriweather" rel="stylesheet" type="text/css" />
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro" rel="stylesheet" type="text/css" />
    <!--<![endif]-->
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
        }

        a[x-apple-data-detectors] {
            color: inherit !important;
            text-decoration: inherit !important;
        }

        #MessageViewBody a {
            color: inherit;
            text-decoration: none;
        }

        p {
            line-height: inherit
        }

        .desktop_hide,
        .desktop_hide table {
            mso-hide: all;
            display: none;
            max-height: 0px;
            overflow: hidden;
        }

        @media (max-width:660px) {
            .desktop_hide table.icons-inner {
                display: inline-block !important;
            }

            .icons-inner {
                text-align: center;
            }

            .icons-inner td {
                margin: 0 auto;
            }

            .image_block img.big,
            .row-content {
                width: 100% !important;
            }

            .mobile_hide {
                display: none;
            }

            .stack .column {
                width: 100%;
                display: block;
            }

            .mobile_hide {
                min-height: 0;
                max-height: 0;
                max-width: 0;
                overflow: hidden;
                font-size: 0px;
            }

            .desktop_hide,
            .desktop_hide table {
                display: table !important;
                max-height: none !important;
            }
        }
    </style>
</head>


<body style="background-color: #ebebeb; margin: 0;padding: 0; -webkit-text-size-adjust: none; text-size-adjust: none;">
<table border="0" cellpadding="0" cellspacing="0" class="nl-container" role="presentation"
    style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #ebebeb;" width="100%">
<tbody>
<tr>
<td>
{{ $header ?? '' }}
<table align="center" border="0" cellpadding="0" cellspacing="0" class="row row-2"
    role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;" width="100%">
<tbody>
    <tr>
        <td>
            <table align="center" border="0" cellpadding="0" cellspacing="0"
                class="row-content stack" role="presentation"
                style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #ffffff; color: #000000; width: 640px;"
                width="640">
                <tbody>
                    <tr>
                        <td class="column column-1"
                            style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; vertical-align: top; padding-top: 5px; padding-bottom: 5px; border-top: 0px; border-right: 0px; border-bottom: 0px; border-left: 0px;"
                            width="100%">
                            <table border="0" cellpadding="0" cellspacing="0"
                                class="paragraph_block block-4" role="presentation"
                                style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; word-break: break-word;"
                                width="100%">
                                <tr>
                                    <td class="pad"
                                        style="padding-top:30px;padding-right:15px;padding-bottom:15px;padding-left:15px;">
                                        <div
                                            style="color:#101112;font-size:19px;font-family:'Source Sans Pro', Tahoma, Verdana, Segoe, sans-serif;font-weight:400;line-height:180%;text-align:left;direction:ltr;letter-spacing:0px;mso-line-height-alt:34.2px;">
                                            <p style="margin: 0; margin-bottom: 19px;">
                                                {{ Illuminate\Mail\Markdown::parse($slot) }}

                                                {{ $subcopy ?? '' }}
                                            <p style="margin: 0;"> </p>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </tbody>
            </table>
        </td>
    </tr>
</tbody>
</table>
{{ $footer ?? '' }}
</td>
</tr>
</tbody>
</table><!-- End -->
</body>
</html>
