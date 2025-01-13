@props([
    'url',
    'color' => 'primary',
    'align' => 'center',
])

<table align="center" border="0" cellpadding="0" cellspacing="0" class="row row-4" role="presentation"
    style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;" width="100%">
<tbody>
<tr>
<td>
<table align="center" border="0" cellpadding="0" cellspacing="0" class="row-content stack"
    role="presentation"
    style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #ffffff; color: #000000; width: 640px;"
    width="640">
<tbody>
<tr>
<td class="column column-1"
    style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; vertical-align: top; padding-top: 5px; padding-bottom: 5px; border-top: 0px; border-right: 0px; border-bottom: 0px; border-left: 0px;"
    width="100%">
<table border="0" cellpadding="0" cellspacing="0" class="button_block block-1"
    role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;"
    width="100%">
    <tr>
        <td class="pad" style="text-align:center;">
            <div align="center" class="alignment">
                <!--[if mso]><v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" style="height:42px;width:86px;v-text-anchor:middle;" arcsize="10%" stroke="false" fillcolor="#2F97C1"><w:anchorlock/><v:textbox inset="0px,0px,0px,0px"><center style="color:#ffffff; font-family:Arial, sans-serif; font-size:16px"><![endif]-->
                <a href="{{ $url }}"
                    style="text-decoration:none;display:inline-block;color:#ffffff;background-color:#2F97C1;border-radius:4px;width:auto;border-top:1px solid #2F97C1;font-weight:400;border-right:1px solid #2F97C1;border-bottom:1px solid #2F97C1;border-left:1px solid #2F97C1;padding-top:5px;padding-bottom:5px;font-family:Arial, Helvetica Neue, Helvetica, sans-serif;text-align:center;mso-border-alt:none;word-break:keep-all;"target="_blank">
                    <span
                        style="padding-left:20px;padding-right:20px;font-size:16px;display:inline-block;letter-spacing:normal;"><span
                            style="line-height: 32px;">
                            {{ $slot }}
                        </span></span>
                </a>
                <!--[if mso]></center></v:textbox></v:roundrect><![endif]-->
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

