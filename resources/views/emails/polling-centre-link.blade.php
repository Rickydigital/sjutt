<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Polling Centre Access</title>

<style>
body{
    margin:0;
    padding:0;
    background:#f5f7fb;
    font-family:'Segoe UI',Arial,sans-serif;
}
a{text-decoration:none;}

@media(max-width:600px){
.main-table{
width:95%!important;
}
}
</style>

</head>

<body>

<table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 0;background:#f5f7fb;">

<tr>
<td align="center">

<table class="main-table" width="650" cellpadding="0" cellspacing="0"
style="background:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 15px 40px rgba(0,0,0,.08);">

<!-- HEADER -->

<tr>
<td align="center"
style="padding:45px;background:linear-gradient(135deg,#582B86,#7f52b5);">

<img
src="https://timetable.sjut.ac.tz/images/logo.png"
width="120"
style="background:#fff;padding:12px;border-radius:12px;">

<h1 style="color:#fff;margin-top:25px;font-size:28px;">
St. John's University of Tanzania
</h1>

<p style="color:#ececec;font-size:16px;margin-top:8px;">
Student Government Election System
</p>

</td>
</tr>

<!-- BODY -->

<tr>

<td style="padding:50px 40px;">

<h2
style="text-align:center;
color:#582B86;
margin-top:0;
font-size:30px;">

Polling Centre Access

</h2>

<p
style="font-size:17px;
color:#555;
text-align:center;
line-height:1.8;">

Dear
<strong>{{ $centre->manager_name ?? 'Polling Centre Manager' }}</strong>,

<br><br>

You have been assigned to manage a polling centre for the upcoming
<strong>{{ $election->title }}</strong>.

</p>

<!-- DETAILS -->

<table
width="100%"
cellpadding="15"
cellspacing="0"
style="margin-top:35px;
background:#f8f9fc;
border-radius:12px;
border:1px solid #ececec;">

<tr>

<td width="45%">
<strong style="color:#582B86;">Polling Centre</strong>
</td>

<td>
{{ $centre->name }}
</td>

</tr>

<tr>

<td>
<strong style="color:#582B86;">Location</strong>
</td>

<td>
{{ $centre->location ?? 'Not specified' }}
</td>

</tr>

<tr>

<td>
<strong style="color:#582B86;">Election</strong>
</td>

<td>
{{ $election->title }}
</td>

</tr>

<tr>

<td>
<strong style="color:#582B86;">Active From</strong>
</td>

<td>
{{ $centre->active_from ?? 'Immediately' }}
</td>

</tr>

<tr>

<td>
<strong style="color:#582B86;">Active Until</strong>
</td>

<td>
{{ $centre->active_until ?? 'Election Close' }}
</td>

</tr>

</table>

<!-- BUTTON -->

<div style="text-align:center;margin:45px 0;">

<a
href="{{ $link }}"
style="
display:inline-block;
padding:18px 45px;
background:#582B86;
color:#fff;
font-size:18px;
font-weight:bold;
border-radius:10px;">

Open Polling Centre

</a>

</div>

<p
style="
text-align:center;
font-size:15px;
color:#666;
line-height:1.8;">

If the button above does not work, copy and paste the link below into your browser.

</p>

<div
style="
background:#f4f4f4;
padding:15px;
border-radius:10px;
word-break:break-all;
font-size:14px;
color:#582B86;">

{{ $link }}

</div>

<!-- SECURITY -->

<div
style="
margin-top:35px;
padding:22px;
background:#fff8e7;
border-left:5px solid #f39c12;
border-radius:10px;">

<h3
style="
margin-top:0;
color:#b9770e;">

🔒 Security Notice

</h3>

<ul
style="
color:#555;
line-height:1.8;
padding-left:18px;">

<li>This link is unique to your polling centre.</li>

<li>Do not share it with unauthorized persons.</li>

<li>Use it only on the official polling centre computer.</li>

<li>Each voter must complete voting before the next voter begins.</li>

<li>If this link is compromised, contact the Dean of Students immediately.</li>

</ul>

</div>

<hr style="margin:45px 0;border:none;border-top:1px solid #eee;">

<p
style="
text-align:center;
color:#888;
font-size:14px;">

This is an automated notification from the
<strong>SJUT Election Management System</strong>.

</p>

</td>

</tr>

<!-- FOOTER -->

<tr>

<td
align="center"
style="
background:#582B86;
padding:28px;
color:white;
font-size:14px;">

<strong>
© {{ date('Y') }} St. John's University of Tanzania
</strong>

<br><br>

SOSJUT Election 

</td>

</tr>

</table>

</td>
</tr>

</table>

</body>
</html>