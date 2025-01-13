<table style="font-family:arial,helvetica,sans-serif;" role="presentation" cellpadding="0" cellspacing="0" width="100%"
    border="0">
    <tbody>
        <tr>
            <td class="v-container-padding-padding"
                style="overflow-wrap:break-word;word-break:break-word;padding:10px;font-family:arial,helvetica,sans-serif;"
                align="left">

                <div>
                    <!--[if mso]><table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-spacing: 0; border-collapse: collapse;  font-family:arial,helvetica,sans-serif;"><tr><td style="font-family:arial,helvetica,sans-serif;" align="center"><v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" style="height:37px; v-text-anchor:middle; width:110px;" arcsize="11%" stroke="f" fillcolor="#623d92"><w:anchorlock/><center style="color:#FFFFFF;font-family:arial,helvetica,sans-serif;"><![endif]-->
                     {{ Illuminate\Mail\Markdown::parse($slot) }}
                    <!--[if mso]></center></v:roundrect></td></tr></table><![endif]-->
                </div>

            </td>
        </tr>
    </tbody>
</table>
