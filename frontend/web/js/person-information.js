$(function(){

	var name = $("#name");
	/*	name.on("keyup",function(){
			var name_val = this.value.replace(/[^\u4E00-\u9FA5\·\u4E00-\u9FA5$]/g,'');
			this.value = name_val;
		})*/
	/*var email_c = $("#email");
		email_c.on("keyup",function(){
			var email_cval = this.value.replace( /[^a-zA-Z0-9_\.\-]$/,'');
			this.value = email_cval;
		})*/

/*验证提示框位置适配*/
	wheight=$(window).height();
	$("#n_validate").css("top","20px");
	/*姓名输入验证*/
	function validateName(){
		/*$("#n_validate").fadeTo(500,1);*/
		$("#n_validate").show();
		$("#name").click(function(){
			$("#n_validate").hide();
		})
	}

	/*验证个人信息是否完整*/
	function cmd(){

		var ipt=document.getElementById("form1").getElementsByTagName("input");
		var ipt_flag=1;
		for(var i=0; i<ipt.length; i++){
			if(ipt[i].value.length == 0){		
				ipt_flag=0;
			}
		}
		if(ipt_flag==1){
			$("#next").removeAttr("disabled");

		}else{
			$("#next").attr("disabled","disabled");
		}
	}

	$("#form1 input").click(function(){
		cmd();
	})
	/*输入每个输入框时判断表单是否完整*/
	
	$('#name').bind('input propertychange', function() {cmd();});
	$('#stu_id').bind('input propertychange', function() {cmd();});
	$('#school-name').bind('input propertychange', function() {cmd();});
	$('#major').bind('input propertychange', function() {cmd();});
	$('#address').bind('input propertychange', function() {cmd();}); 
	$('#email').bind('input propertychange', function(){cmd();});
	
	$("#next").click(function(){

		var school=$("#school-name").html();
		var major=$("#major").html();
		  
			/*ss=s2(school,major);*/
			var major_len=major.length-5,
			    college=major.substr(0,major_len),
			    adgree = $("#adgree").val();
			    
			ss(school,college,adgree);
			/*alert(s1+s2);*/
			$("#school_id").val(s1+s2);

			sname= $("#name").val();
			RegCellName = /^[\u4e00-\u9fa5\·\•\●]*$/;
			name_falg=sname.search(RegCellName);
			/*alert(sname.length);*/
			if(sname.length<2 || name_falg=="-1"){
				falg=-1;
			}else{
				falg=0;
			}
			
			if(falg==-1){
				$("#error").html("请输入真实姓名");
				$("#n_validate").show().delay(3000).fadeOut();
				/*$("#name").click(function(){
					$("#n_validate").hide();
				})*/
				$("#name").focus();
			}
		
		if(falg!=-1){
			stu_id= $("#stu_id").val();
			/*RegCellStuid = /^([0-9]*)?$/;*/
		        //RegCellStuid =/^(\d{8}|\d{10}|\d{12})$/;
			//falg1=stu_id.search(RegCellStuid);
                        falg1=1;
                        stu_len=stu_id.length;
			if(falg1==-1 || stu_len<8){
				$("#error").html("请输入正确的学生证号");
				$("#n_validate").show().delay(3000).fadeOut();
				/*$("#stu_id").click(function(){
					$("#n_validate").hide();
				})*/
				$("#stu_id").focus();
			}
		}

		if(falg!=-1 && falg1!=-1){
			address=$("#address").val();
			RegCellAddress =/[\u4e00-\u9fa5A-Za-z0-9]+/g;
			falg2=address.search(RegCellAddress);
			if(falg2==-1){
				$("#error").html("地址包含非法字符");
				$("#n_validate").show().delay(3000).fadeOut();
				/*$("#stu_id").click(function(){
					$("#n_validate").hide();
				})*/
				$("#address").focus();
			}

		}
		if(falg!=-1 && falg1!=-1 && falg2!=-1){
			c_email = $("#email").val();
			RegCellEmail = /[a-zA-Z0-9_\.\-]/;
			falg3 = c_email.search(RegCellEmail);
			if(falg3==-1){
				$("#error").html("邮箱包含非法字符");
				$("#n_validate").show().delay(3000).fadeOut();
				$("#email").focus();
			}
		}

		var next = $("#next"),
			z_name = $("#name").val(),
			zstu_id = $("#stu_id").val(),
			z_grade = $("#grade").val(),
			zschool_id = $("#school_id").val(),
			zdorm = $("#address").val(),
			loading = $("#loading_masker"),
			loadingImg = $("#loadingImg"),
			mask = $("#masker"),
			school_name = $("#school-name").html(),
			email = $("#email").val();
			if(school_name=="北京大学"){
				email = email+'@pku.edu.cn';
			}else if(school_name=="北京大学（医学部）"){
				email = email+'@bjmu.edu.cn';
			}else if(school_name=="清华大学"){
				email = email+'@mails.tsinghua.edu.cn';
			}else if(school_name=="中国人民大学"){
				email = email+'@ruc.edu.cn';
			}else if(school_name=="北京师范大学"){
				email = email+'@mail.bnu.edu.cn';
			}else if(school_name=="浙江大学"){
				email = email+'@zju.edu.cn';
			}else if(school_name=="北京航空航天大学"){
				email = email+'@buaa.edu.cn';
			}else if(school_name=="对外经济贸易大学"){
				email = email+'@uibe.edu.cn';
			}else if(school_name=="北京理工大学"){
				email = email+'@bit.edu.cn';
			}else if(school_name=="哈尔滨工业大学"){
				email = email+'@hit.edu.cn';
			}

		if(falg!=-1 && falg1!=-1 && falg2!=-1 && falg3!=-1 && stu_len>=8){
			
			if(next.hasClass("disabled")) return false;
			next.addClass('disabled');	 
			mask.addClass("masker-60").show();
			setTimeout(function(){
				loading.css({height:"100%"}).show();	
			},200);						
			TOOLS.ajax({
				url:"./index.php?r=loan/bank",
				data:{name:z_name,stu_id:zstu_id,grade:z_grade,school_id:zschool_id,dorm:zdorm,email:email},
				dataType:"json",
				type:"post",
				fnSuccess:function(data){
					if(data.stat == "1"){
						loadingImg.hide();	
						window.location.href= "./index.php?r=loan/mail&email="+email;
					}else{
						loadingImg.hide();
						MessageBox.alert({type:"message",txt:"当前学生信息已被占用，请核实重新填写。"});
						next.removeClass("disabled");
					}
					mask.removeClass("masker-60");
				},
				fnError:function(){}
			});



		}
		
	})

/*个人信息验证有误*/
function error2(){
	$(".mask6").show();
	var wheight=$(window).height();
	var pi_error=$("#pi_error").height();
	$("#pi_error").css("top",Math.round((wheight-pi_error)/2-10));
	$("#close7").click(function(){
		$(".mask6").hide();
	})
}

/*个人信息被占用提示框*/
function error3(){
	$(".mask8").height($(window).height());
	var wheight=$(window).height();
	var cschool=$("#error3").height();
	$("#error3").css("top",Math.round((wheight-cschool)/6));
	$("#e3-close").click(function(){
		$(".mask8").hide();
	})
}

})    /*$(function)结束*/

function ss(school,college,adgree){
	if(school=="北京大学（医学部）"||school=="北京大学"){
          if(school=="北京大学（医学部）"){
            s1="101";
          }else{
            s1="102";
          }
	  if(college=="临床医学8年制" || college=="城市与环境学院" && adgree=="本科"){s2="01";}
	  else if(college=="基础医学8年制" || college=="地球与空间科学学院" && adgree=="本科"){s2="02";}
	  else if(college=="口腔医学8年制" || college=="法学院" && adgree=="本科"){s2="03";}
	  else if(college=="预防医学7年制" || college=="工学院" && adgree=="本科"){s2="04";}
	  else if(college=="应用药学6年制" || college=="光华管理学院" && adgree=="本科"){s2="05";}
	  else if(college=="临床医学5年制" || college=="国际关系学院" && adgree=="本科"){s2="06";}
	  else if(college=="口腔医学5年制" || college=="化学与分子工程学院" && adgree=="本科"){s2="07";}
	  else if(college=="生物医学英语" || college=="环境科学与工程学院" && adgree=="本科"){s2="08";}
	  else if(college=="预防医学5年制" || college=="经济学院" && adgree=="本科"){s2="09";}
	  else if(college=="应用药学4年制" || college=="考古文博学院" && adgree=="本科"){s2="10";}
	  else if(college=="护理学" || college=="历史学系" && adgree=="本科"){s2="11";}
	  else if(college=="医学实验技术" || college=="社会学系" && adgree=="本科"){s2="12";}
	  else if(college=="医学检验技术" || college=="生命科学学院" && adgree=="本科"){s2="13";}
	  else if(college=="口腔医学技术" || college=="数学科学学院" && adgree=="本科"){s2="14";}
           
	  else if(college=="外国语学院" && adgree=="本科" || college=="基础医学院"){s2="15";}
	  else if(college=="物理学院" && adgree=="本科" || college=="药学院"){s2="16";}
	  else if(college=="心理学系" && adgree=="本科" || college=="公共卫生学院"){s2="17";}
	  else if(college=="新闻与传播学院" && adgree=="本科" || college=="护理学院"){s2="18";}
	  else if(college=="信息管理系" && adgree=="本科" || college=="医学人文研究院"){s2="19";}
	  else if(college=="信息科学技术学院" && adgree=="本科" || college=="北京大学第一医院"){s2="20";}
	  else if(college=="艺术学院" && adgree=="本科" || college=="北京大学人民医院"){s2="21";}
	  else if(college=="元培学院" && adgree=="本科" || college=="北京大学第三医院"){s2="22";}
	  else if(college=="哲学系" && adgree=="本科" || college=="北京大学口腔医院"){s2="23";}
	  else if(college=="政府管理学院" && adgree=="本科" || college=="北京大学肿瘤医院"){s2="24";}
	  else if(college=="中国语言文学系" && adgree=="本科" || college=="北京大学第六医院"){s2="25";}
          
	  else if(college=="北京大学首钢医院" || college=="城市与环境学院" && adgree=="研究生"){s2="26";}
	  else if(college=="北京大学国际医院" || college=="地球与空间科学学院" && adgree=="研究生"){s2="27";}
	  else if(college=="北京积水潭医院" || college=="法学院" && adgree=="研究生"){s2="28";}
	  else if(college=="卫生部北京医院" || college=="工学院" && adgree=="研究生"){s2="29";}
	  else if(college=="北京世纪坛医院" || college=="光华管理学院" && adgree=="研究生"){s2="30";}
	  else if(college=="卫生部中日友好医院" || college=="国际关系学院" && adgree=="研究生"){s2="31";}
	  else if(college=="北京航天中心医院" || college=="化学与分子工程学院" && adgree=="研究生"){s2="32";}
	  else if(college=="北京地坛医院" || college=="环境科学与工程学院" && adgree=="研究生"){s2="33";}
	  else if(college=="北京民用航空总医院" || college=="经济学院" && adgree=="研究生"){s2="34";}
	  else if(college=="首都儿科研究所" || college=="考古文博学院" && adgree=="研究生"){s2="35";}
	  else if(college=="北京京煤集团总医院" || college=="历史学系" && adgree=="研究生"){s2="36";}
	  else if(college=="北京仁和医院" || college=="社会学系" && adgree=="研究生"){s2="37";}
	  else if(college=="解放军306医院" || college=="生命科学学院" && adgree=="研究生"){s2="38";}
	  else if(college=="解放军302医院" || college=="数学科学学院" && adgree=="研究生"){s2="39";}
	  else if(college=="北京回龙观医院" || college=="外国语学院" && adgree=="研究生"){s2="40";}
	  else if(college=="物理学院" && adgree=="研究生"){s2="41";}
	  else if(college=="心理学系" && adgree=="研究生"){s2="42";}
	  else if(college=="新闻与传播学院" && adgree=="研究生"){s2="43";}
	  else if(college=="信息管理系" && adgree=="研究生"){s2="44";}
	  else if(college=="信息科学技术学院" && adgree=="研究生"){s2="45";}
	  else if(college=="艺术学院" && adgree=="研究生"){s2="46";}
	  else if(college=="元培学院" && adgree=="研究生"){s2="47";}
	  else if(college=="哲学系" && adgree=="研究生"){s2="48";}
	  else if(college=="政府管理学院" && adgree=="研究生"){s2="49";}
	  else if(college=="中国语言文学系" && adgree=="研究生"){s2="50";}
          else if(college=="软件与微电子学院" && adgree=="研究生"){s2="51";}
	}else if(school=="浙江大学"){
	  s1="103";
          if(adgree=="本科"){
	    if(college=="人文学院"){s2="01";}
	    else if(college=="外国语言文化与国际交流学院"){s2="02";}
	    else if(college=="传媒与国际文化学院"){s2="03";}
	    else if(college=="经济学院"){s2="04";}
	    else if(college=="光华法学院"){s2="05";}
	    else if(college=="教育学院"){s2="06";}
	    else if(college=="管理学院"){s2="07";}
	    else if(college=="公共管理学院"){s2="08";}
	    else if(college=="数学科学学院"){s2="09";}
	    else if(college=="物理学系"){s2="10";}
	    else if(college=="化学系"){s2="11";}
	    else if(college=="地球科学学院"){s2="12";}
	    else if(college=="心理与行为科学系"){s2="13";}
	    else if(college=="机械工程学院"){s2="14";}
	    else if(college=="材料科学与工程学院"){s2="15";}
	    else if(college=="能源工程学院"){s2="16";}
	    else if(college=="电气工程学院"){s2="17";}
	    else if(college=="建筑工程学院"){s2="18";}
	    else if(college=="化学工程与生物工程学院"){s2="19";}
	    else if(college=="航空航天学院"){s2="20";}
	    else if(college=="高分子科学与工程学系"){s2="21";}
	    else if(college=="海洋学院"){s2="22";}
	    else if(college=="光电科学与工程学院"){s2="23";}
	    else if(college=="信息与电子工程学院"){s2="24";}
	    else if(college=="控制科学与工程学院"){s2="25";}
	    else if(college=="计算机科学与技术学院"){s2="26";}
	    else if(college=="生物医学工程与仪器科学学院"){s2="27";}
	    else if(college=="软件学院"){s2="28";}
	    else if(college=="生命科学学院"){s2="29";}
	    else if(college=="生物系统工程与食品科学学院"){s2="30";}
	    else if(college=="环境与资源学院"){s2="31";}
	    else if(college=="农业与生物技术学院"){s2="32";}
	    else if(college=="动物科学学院"){s2="33";}
	    else if(college=="医学院"){s2="34";}
	    else if(college=="药学院"){s2="35";}
          }else if(adgree=="研究生"){
	    if(college=="人文学院"){s2="36";}
	    else if(college=="材料科学与工程学院"){s2="37";}
	    else if(college=="传媒与国际文化学院"){s2="38";}
	    else if(college=="地球科学学院"){s2="39";}
	    else if(college=="电气工程学院"){s2="40";}
	    else if(college=="动物科学学院"){s2="41";}
	    else if(college=="高分子科学与工程学系"){s2="42";}
	    else if(college=="公共管理学院"){s2="43";}
	    else if(college=="管理学院"){s2="44";}
	    else if(college=="光电科学与工程学院"){s2="45";}
	    else if(college=="光华法学院"){s2="46";}
	    else if(college=="海洋学院"){s2="47";}
	    else if(college=="航空航天学院"){s2="48";}
	    else if(college=="化学工程与生物工程学院"){s2="49";}
	    else if(college=="化学系"){s2="50";}
	    else if(college=="环境与资源学院"){s2="51";}
	    else if(college=="机械工程学院"){s2="52";}
	    else if(college=="计算机科学与技术学院"){s2="53";}
	    else if(college=="建筑工程学院"){s2="54";}
	    else if(college=="教育学院"){s2="55";}
	    else if(college=="经济学院"){s2="56";}
	    else if(college=="控制科学与工程学院"){s2="57";}
	    else if(college=="能源工程学院"){s2="58";}
	    else if(college=="农业与生物技术学院"){s2="59";}
	    else if(college=="软件学院"){s2="60";}
	    else if(college=="生命科学学院"){s2="61";}
	    else if(college=="生物系统工程与食品科学学院"){s2="62";}
	    else if(college=="生物医学工程与仪器科学学院"){s2="63";}
	    else if(college=="数学科学学院"){s2="64";}
	    else if(college=="外国语言文化与国际交流学院"){s2="65";}
	    else if(college=="物理学系" ){s2="66";}
	    else if(college=="心理与行为科学系"){s2="67";}
	    else if(college=="信息与电子工程学院"){s2="68";}
	    else if(college=="药学院"){s2="69";}
            else if(college=="医学院"){s2="70";}
          }
	}else if(school=="中国人民大学"){
	  s1="104";
          if(adgree=="本科"){
            if(college=="环境学院"){s2="01";}
            else if(college=="信息学院"){s2="02";}
            else if(college=="哲学院"){s2="03";}
            else if(college=="文学院"){s2="04";}
            else if(college=="历史学院"){s2="05";}
            else if(college=="艺术学院"){s2="06";}
            else if(college=="外国语学院"){s2="07";}
            else if(college=="新闻学院"){s2="08";}
            else if(college=="农业与农村发展学院"){s2="09";}
            else if(college=="社会与人口学院"){s2="10";}
            else if(college=="公共管理学院"){s2="11";}
            else if(college=="信息资源管理学院"){s2="12";}
            else if(college=="财政金融学院"){s2="13";}
            else if(college=="统计学院"){s2="14";}
            else if(college=="商学院"){s2="15";}
            else if(college=="劳动人事学院"){s2="16";}
            else if(college=="法学院"){s2="17";}
            else if(college=="马克思主义学院"){s2="18";}
            else if(college=="国际关系学院"){s2="19";}
            else if(college=="国学院"){s2="20";}
            else if(college=="经济学院"){s2="21";}
            else if(college=="理学院·心理学系"){s2="22";}
            else if(college=="理学院·化学系"){s2="23";}
            else if(college=="理学院·物理学系"){s2="24";}
          }else if(adgree=="研究生"){
            if(college=="环境学院"){s2="25";}
            else if(college=="信息学院"){s2="26";}
            else if(college=="哲学院"){s2="27";}
            else if(college=="文学院"){s2="28";}
            else if(college=="历史学院"){s2="29";}
            else if(college=="艺术学院"){s2="30";}
            else if(college=="外国语学院"){s2="31";}
            else if(college=="新闻学院"){s2="32";}
            else if(college=="农业与农村发展学院"){s2="33";}
            else if(college=="社会与人口学院"){s2="34";}
            else if(college=="公共管理学院"){s2="35";}
            else if(college=="信息资源管理学院"){s2="36";}
            else if(college=="财政金融学院"){s2="37";}
            else if(college=="统计学院"){s2="38";}
            else if(college=="商学院"){s2="39";}
            else if(college=="劳动人事学院"){s2="40";}
            else if(college=="法学院"){s2="41";}
            else if(college=="马克思主义学院"){s2="42";}
            else if(college=="国际关系学院"){s2="43";}
            else if(college=="国学院"){s2="44";}
            else if(college=="经济学院"){s2="45";}
            else if(college=="理学院·心理学系"){s2="46";}
            else if(college=="理学院·化学系"){s2="47";}
            else if(college=="理学院·物理学系"){s2="48";}
            else if(college=="教育学院"){s2="49";}
            else if(college=="汉青研究院"){s2="50";}
          }
	}else if(school=="北京师范大学"){
	  s1="105";
          if(adgree=="本科"){
            if(college=="教育学部"){s2="01";}
            else if(college=="哲学与社会学学院"){s2="02";}
            else if(college=="经济与工商管理学院"){s2="03";}
            else if(college=="法学院"){s2="04";}
            else if(college=="心理学院"){s2="05";}
            else if(college=="体育与运动学院"){s2="06";}
            else if(college=="文学院"){s2="07";}
            else if(college=="外国语言文学学院"){s2="08";}
            else if(college=="艺术与传媒学院"){s2="09";}
            else if(college=="历史学院"){s2="10";}
            else if(college=="数学科学学院"){s2="11";}
            else if(college=="物理学系"){s2="12";}
            else if(college=="化学学院"){s2="13";}
            else if(college=="天文系"){s2="14";}
            else if(college=="地理学与遥感科学学院"){s2="15";}
            else if(college=="环境学院"){s2="16";}
            else if(college=="资源学院"){s2="17";}
            else if(college=="生命科学学院"){s2="18";}
            else if(college=="信息科学与技术学院"){s2="19";}
            else if(college=="政府管理学院"){s2="20";}
            else if(college=="国家基础学科拔尖学生 培养试验计划"){s2="21";}
          }else if(adgree=="研究生"){
            if(college=="教育学部"){s2="25";}
            else if(college=="哲学与社会学学院"){s2="22";}
            else if(college=="经济与工商管理学院"){s2="23";}
            else if(college=="马克思主义学院"){s2="24";}
            else if(college=="法学院"){s2="26";}
            else if(college=="政府管理学院"){s2="27"}
            else if(college=="心理学院"){s2="28";}
            else if(college=="脑与认知科学研究院"){s2="29";}
            else if(college=="体育与运动学院"){s2="30";}
            else if(college=="文学院"){s2="31";}
            else if(college=="外国语言文学学院"){s2="32";}
            else if(college=="历史学院"){s2="33";}
            else if(college=="古籍与传统文化研究院"){s2="34";}
            else if(college=="经济与资源管理研究院"){s2="35";}
            else if(college=="数学科学学院"){s2="36";}
            else if(college=="物理学系"){s2="37";}
            else if(college=="核科学与技术学院"){s2="38";}
            else if(college=="化学学院"){s2="40";}
            else if(college=="天文系"){s2="41";}
            else if(college=="地理学与遥感科学学院"){s2="42";}
            else if(college=="环境学院"){s2="43";}
            else if(college=="资源学院"){s2="45";}
            else if(college=="生命科学学院"){s2="44";}
            else if(college=="信息科学与技术学院"){s2="39";}
            else if(college=="社会发展与公共政策学院/中国社会管理研究院"){s2="46";}
            else if(college=="系统科学学院"){s2="47";}
            else if(college=="全球变化与地球系统科学研究院"){s2="48";}
            else if(college=="减灾与应急管理研究院/地表过程与资源生态国家重点实验室"){s2="49";}
            else if(college=="艺术与传媒学院"){s2="50";}
            else if(college=="水科学研究院"){s2="51";}
            else if(college=="刑事法律科学研究院"){s2="52";}
            else if(college=="汉语文化学院"){s2="53";}
            else if(college=="国民核算研究院"){s2="54";}
          }
	}else if(school=="清华大学"){
	  s1="106";
          if(adgree=="本科"){
            if(college=="建筑学院"){s2="01";}
            else if(college=="土木水利学院"){s2="02";}
            else if(college=="环境学院"){s2="03";}
            else if(college=="机械工程学院"){s2="04";}
            else if(college=="电机工程与应用电子技术系"){s2="05";}
            else if(college=="信息科学技术学院"){s2="06";}
            else if(college=="交叉信息研究院"){s2="07";}
            else if(college=="航天航空学院"){s2="08";}
            else if(college=="工程物理系"){s2="09";}
            else if(college=="化学工程系"){s2="10";}
            else if(college=="材料学院"){s2="11";}
            else if(college=="理学院"){s2="12";}
            else if(college=="生命科学学院"){s2="13";}
            else if(college=="医学院"){s2="14";}
            else if(college=="生物医学工程系"){s2="15";}
            else if(college=="北京协和医学院"){s2="16";}
            else if(college=="经济管理学院"){s2="17";}
            else if(college=="人文学院"){s2="18";}
            else if(college=="社会科学学院"){s2="19";}
            else if(college=="法学院"){s2="20";}
            else if(college=="新闻与传播学院"){s2="21";}
          }else if(adgree=="研究生"){
            if(college=="建筑学院"){s2="22";}
            else if(college=="土木水利学院"){s2="23";}
            else if(college=="环境学院"){s2="24";}
            else if(college=="机械工程学院"){s2="25";}
            else if(college=="电机工程与应用电子技术系"){s2="26";}
            else if(college=="信息科学技术学院"){s2="27";}
            else if(college=="交叉信息研究院"){s2="28";}
            else if(college=="航天航空学院"){s2="29";}
            else if(college=="工程物理系"){s2="30";}
            else if(college=="化学工程系"){s2="31";}
            else if(college=="材料学院"){s2="32";}
            else if(college=="理学院"){s2="33";}
            else if(college=="生命科学学院"){s2="34";}
            else if(college=="医学院"){s2="35";}
            else if(college=="生物医学工程系"){s2="36";}
            else if(college=="北京协和医学院"){s2="37";}
            else if(college=="经济管理学院"){s2="38";}
            else if(college=="人文学院"){s2="39";}
            else if(college=="社会科学学院"){s2="40";}
            else if(college=="法学院"){s2="41";}
            else if(college=="新闻与传播学院"){s2="42";}
          }
	}else if(school=="北京航空航天大学"){
	  s1="107";
          if(adgree=="本科"){
            if(college=="材料科学与工程学院"){s2="01";}
            else if(college=="电子信息工程学院"){s2="02";}
            else if(college=="自动化科学与电气工程学院"){s2="03";}
            else if(college=="能源与动力工程学院"){s2="04";}
            else if(college=="航空科学与工程学院"){s2="05";}
            else if(college=="计算机学院"){s2="06";}
            else if(college=="机械工程与自动化学院"){s2="07";}
            else if(college=="经济管理学院"){s2="08";}
            else if(college=="数学与系统科学学院"){s2="09";}
            else if(college=="生物与医学工程学院"){s2="10";}
            else if(college=="学院"){s2="11";}
            else if(college=="理学院"){s2="12";}
            else if(college=="生命科学学院"){s2="13";}
            else if(college=="医学院"){s2="14";}
            else if(college=="生物医学工程系"){s2="15";}
            else if(college=="北京协和医学院"){s2="16";}
            else if(college=="经济管理学院"){s2="17";}
            else if(college=="人文学院"){s2="18";}
            else if(college=="社会科学学院"){s2="19";}
            else if(college=="法学院"){s2="20";}
            else if(college=="新闻与传播学院"){s2="21";}
          }else if(adgree=="研究生"){
            if(college=="建筑学院"){s2="22";}
            else if(college=="土木水利学院"){s2="23";}
            else if(college=="环境学院"){s2="24";}
            else if(college=="机械工程学院"){s2="25";}
            else if(college=="电机工程与应用电子技术系"){s2="26";}
            else if(college=="信息科学技术学院"){s2="27";}
            else if(college=="交叉信息研究院"){s2="28";}
            else if(college=="航天航空学院"){s2="29";}
            else if(college=="工程物理系"){s2="30";}
            else if(college=="化学工程系"){s2="31";}
            else if(college=="材料学院"){s2="32";}
            else if(college=="理学院"){s2="33";}
            else if(college=="生命科学学院"){s2="34";}
            else if(college=="医学院"){s2="35";}
            else if(college=="生物医学工程系"){s2="36";}
            else if(college=="北京协和医学院"){s2="37";}
            else if(college=="经济管理学院"){s2="38";}
            else if(college=="人文学院"){s2="39";}
            else if(college=="社会科学学院"){s2="40";}
            else if(college=="法学院"){s2="41";}
            else if(college=="新闻与传播学院"){s2="42";}
          }
	}else if(school=="对外经济贸易大学"){
          s1="108";
          if(adgree=="本科"){
            if(college=="国际经济与贸易学院"){s2="01"}
            else if(college=="保险学院"){s2="02"}
            else if(college=="法学院"){s2="03"}
            else if(college=="公共管理学院"){s2="04"}
            else if(college=="国际关系学院"){s2="05"}
            else if(college=="国际商学院"){s2="06"}
            else if(college=="金融学院"){s2="07"}
            else if(college=="统计学院"){s2="08"}
            else if(college=="外语学院"){s2="09"}
            else if(college=="信息学院"){s2="10"}
            else if(college=="英语学院"){s2="11"}
            else if(college=="中国语言文学学院"){s2="12"}
          }else if(adgree=="研究生"){
            if(college=="国际经济与贸易学院"){s2="13"}
            else if(college=="保险学院"){s2="14"}
            else if(college=="法学院"){s2="15"}
            else if(college=="公共管理学院"){s2="16"}
            else if(college=="国际关系学院"){s2="17"}
            else if(college=="国际商学院"){s2="18"}
            else if(college=="金融学院"){s2="19"}
            else if(college=="统计学院"){s2="20"}
            else if(college=="外语学院"){s2="21"}
            else if(college=="信息学院"){s2="22"}
            else if(college=="英语学院"){s2="23"}
            else if(college=="中国语言文学学院"){s2="24"}
          }
        }else if(school=="北京理工大学"){
          s1="109";
          if(adgree=="本科"){
            if(college=="宇航学院"){s2="01"}
            else if(college=="机电学院"){s2="02"}
            else if(college=="机械与车辆学院"){s2="03"}
            else if(college=="光电学院"){s2="04"}
            else if(college=="信息与电子学院"){s2="05"}
            else if(college=="自动化学院"){s2="06"}
            else if(college=="计算机学院"){s2="07"}
            else if(college=="软件学院"){s2="08"}
            else if(college=="材料学院"){s2="09"}
            else if(college=="化工与环境学院"){s2="10"}
            else if(college=="生命学院"){s2="11"}
            else if(college=="数学学院"){s2="12"}
            else if(college=="物理学院"){s2="13"}
            else if(college=="化学学院"){s2="14"}
            else if(college=="管理与经济学院"){s2="15"}
            else if(college=="人文与社会科学学院"){s2="16"}
            else if(college=="法学院"){s2="17"}
            else if(college=="外国语学院"){s2="18"}
            else if(college=="设计与艺术学院"){s2="19"}
            else if(college=="国际学院"){s2="20"}
            else if(college=="基础学院"){s2="21"}
          }else if(adgree=="研究生"){
            if(college=="宇航学院"){s2="22"}
            else if(college=="机电学院"){s2="23"}
            else if(college=="机械与车辆学院"){s2="24"}
            else if(college=="光电学院"){s2="25"}
            else if(college=="信息与电子学院"){s2="26"}
            else if(college=="自动化学院"){s2="27"}
            else if(college=="计算机学院"){s2="28"}
            else if(college=="软件学院"){s2="29"}
            else if(college=="材料学院"){s2="30"}
            else if(college=="化工与环境学院"){s2="31"}
            else if(college=="生命学院"){s2="32"}
            else if(college=="数学学院"){s2="33"}
            else if(college=="物理学院"){s2="34"}
            else if(college=="化学学院"){s2="35"}
            else if(college=="管理与经济学院"){s2="36"}
            else if(college=="人文与社会科学学院"){s2="37"}
            else if(college=="法学院"){s2="38"}
            else if(college=="外国语学院"){s2="39"}
            else if(college=="设计与艺术学院"){s2="40"}
            else if(college=="国际学院"){s2="41"}
            else if(college=="基础学院"){s2="42"}
          }
        }else if(school=="哈尔滨工业大学"){
          s1="110";
          if(adgree=="本科"){
            if(college=="材料科学与工程学院"){s2="01"}
            else if(college=="电气工程及自动化学院"){s2="02"}
            else if(college=="电子与信息工程学院"){s2="03"}
            else if(college=="法学院"){s2="04"}
            else if(college=="航天学院"){s2="05"}
            else if(college=="化工学院"){s2="06"}
            else if(college=="机电工程学院"){s2="07"}
            else if(college=="基础与交叉科学研究院"){s2="08"}
            else if(college=="计算机科学与技术学院"){s2="09"}
            else if(college=="建筑学院"){s2="10"}
            else if(college=="交通科学与工程学院"){s2="11"}
            else if(college=="经济与管理学院"){s2="12"}
            else if(college=="理学院"){s2="13"}
            else if(college=="能源科学与工程学院"){s2="14"}
            else if(college=="人文与社会科学学院"){s2="15"}
            else if(college=="软件学院"){s2="16"}
            else if(college=="生命科学与技术学院"){s2="17"}
            else if(college=="市政环境工程学院"){s2="18"}
            else if(college=="土木工程学院"){s2="19"}
            else if(college=="外国语学院"){s2="20"}
          }else if(adgree=="研究生"){
            if(college=="材料科学与工程学院"){s2="21"}
            else if(college=="电气工程及自动化学院"){s2="22"}
            else if(college=="电子与信息工程学院"){s2="23"}
            else if(college=="法学院"){s2="24"}
            else if(college=="航天学院"){s2="25"}
            else if(college=="化工学院"){s2="26"}
            else if(college=="机电工程学院"){s2="27"}
            else if(college=="基础与交叉科学研究院"){s2="28"}
            else if(college=="计算机科学与技术学院"){s2="29"}
            else if(college=="建筑学院"){s2="30"}
            else if(college=="交通科学与工程学院"){s2="31"}
            else if(college=="经济与管理学院"){s2="32"}
            else if(college=="理学院"){s2="33"}
            else if(college=="能源科学与工程学院"){s2="34"}
            else if(college=="人文与社会科学学院"){s2="35"}
            else if(college=="软件学院"){s2="36"}
            else if(college=="生命科学与技术学院"){s2="37"}
            else if(college=="市政环境工程学院"){s2="38"}
            else if(college=="土木工程学院"){s2="39"}
            else if(college=="外国语学院"){s2="40"}
          }
        }
}
