<!DOCTYPE html>
<html class="mobile-notes-variant" lang="en"><!--full-srceen-->
<head>
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0" user-scalable="no">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>身份验证</title>
<link rel="apple-touch-icon-precomposed" sizes="144x144" href="/apple-touch-icon-precomposed-144.png">
<link rel="apple-touch-icon-precomposed" sizes="114x114" href="/apple-touch-icon-precomposed-114.png">
<link rel="apple-touch-icon-precomposed" sizes="72x72" href="img/logo-72.png">
<link rel="stylesheet" type="text/css" media="all" href="css/reset.css" />
<link rel="stylesheet" type="text/css" media="all" href="css/base.css" />
<link rel="stylesheet" type="text/css" media="all" href="css/fonts.css" />
<link rel="stylesheet" type="text/css" media="all" href="css/widget.css" />
</head>
<body>
	<div class="container">
		<div class="screen-content">			
			<div class="content">
				<div class="fail-box">				
					<h1>身份验证未通过</h1>
					<p>
						可能未通过的原因：<br />1、填写的信息与实际情况不符<br />2、填写错误次数过多
					</p>					
				</div>
			</div>
		</div>
	</div>	
</body>
<script src="http://res.wx.qq.com/open/js/jweixin-1.0.0.js" type="text/javascript" charset="utf-8"></script>
<script type="text/javascript" charset="utf-8">
     wx.config(<?php echo $js->config(array('hideOptionMenu'), false, true) ?>);
     wx.ready(function(){
         wx.hideOptionMenu();
     });
 </script>
</html>
