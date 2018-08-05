<!DOCTYPE html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>
<body style="font-family: Calibri; box-sizing: border-box; background-color: #eeeeee; color: #808080; height: 100%; hyphens: auto; line-height: 1.4; margin: 0; -moz-hyphens: auto; -ms-word-break: break-all; width: 100% !important; -webkit-hyphens: auto; -webkit-text-size-adjust: none; word-break: break-word;">
    <style>
        @media  only screen and (max-width: 600px) {
            .inner-body {
                width: 100% !important;
            }

            .footer {
                width: 100% !important;
            }
        }

        @media  only screen and (max-width: 500px) {
            .button {
                width: 100% !important;
            }
        }
    </style>
	<div style="padding: 4% 10%; font-size:18px;">
		<span style="padding-bottom: 10px;text-align: center;float: left;width: 100%;">
			<img src="{{url('/images/logoBig.png')}}" style="max-height: 60px;" />
		</span>
		<hr>
		</br>      
                Hi {!! $templateData['subscriber']->name !!},
		</br> 
		{!! strip_tags($body) !!}
		<hr>
		Thanks,<br />
		RYEC		
    </div>
</body>
</html>


