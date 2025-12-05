var canClick = true;
var installIndex = 0;

String.prototype.format = function (args) {
	if (arguments.length > 0) {
		var result = this;
		if (arguments.length === 1 && typeof args == "object") {
			for (var key in args) {
				var reg = new RegExp("({" + key + "})", "g");
				result = result.replace(reg, args[key]);
			}
		} else {
			for (var i = 0; i < arguments.length; i++) {
				if (arguments[i] === undefined) {
					return "";
				} else {
					var reg = new RegExp("({[" + i + "]})", "g");
					result = result.replace(reg, arguments[i]);
				}
			}
		}
		return result;
	} else {
		return this;
	}
};

/**
 * 将内容推送到内容里面
 */
function pushSuccessTableToBox(successLine) {
	var installBox = document.getElementById("install_message");

	var div = document.createElement("div");
	div.className = "item-cell";
	var lineHtml = `
            <div style="display: flex;align-items: center;">
            <div class="layui-icon green">&#xe605;</div>
        <div style="margin-left: 10px;">创建数据表{0}完成！</div>
        </div>
        <div>{1}</div>
        `;
	div.innerHTML = lineHtml.format(successLine[0], successLine[1]);

	installBox.append(div);
}

function showParts(index) {
	function getRndInteger(min, max) {
		return Math.floor(Math.random() * (max - min)) + min;
	}

	if (typeof successTables !== "undefined") {
		if (index <= successTables.length) {
			setTimeout(function () {
				pushSuccessTableToBox(successTables[index]);
				showParts(++index);
			}, getRndInteger(50, 150));
		}

		if (index === successTables.length) {
			goStep(5);
		}
	}
}

function goStep(step) {
	//var form = document.getElementsByTagName('form')[0];
	if (canClick === false) return;
	
	canClick = false;
	if (step == 4) {
		document.getElementById('jinyon').disabled = true; 
		document.getElementById('jinyon').innerText = "等待安装"; // 更
    }

    document.main_form.action = "?step=" + step;
    document.main_form.submit();

    // 这里可以添加一个事件监听器，监听表单提交完成后结束loading
    document.main_form.onsubmit = function() {
        // 提交后结束loading
        document.getElementById('jinyon').disabled = false; 
		document.getElementById('jinyon').innerText = "继续"; 
    };
	// form.action = "?step=" + step;
	// window.location.href = "?step=" + step;
}

let countdown = 180;
let timer;

function sendVerificationCode() {
	// 开始倒计时
	timer = setInterval(function () {
		if (countdown > 0) {
			document.querySelector(".code-btn").innerHTML =
				countdown + "秒后可重新发送";
			document.querySelector(".code-btn").disabled = true;
			countdown--;
		} else {
			clearInterval(timer);
			document.querySelector(".code-btn").innerHTML = "重新发送验证码";
			document.querySelector(".code-btn").disabled = false;
			countdown = 180;
		}
	}, 1000);
}

function sendCode(e) {
	e.preventDefault();
	var mobile = document.getElementById("mobile").value;

	if (mobile == "") {
		alert("请输入手机号");
		return;
	}

	var regex = /^1[3-9]\d{9}$/;

	if (regex.test(mobile) == false) {
		alert("请输入正确的手机号");
		return;
	}

	// 创建一个新的XMLHttpRequest对象
	var xhr = new XMLHttpRequest();

	// 配置请求的类型（POST），URL以及是否异步
	xhr.open("POST", "install.php", true);

	// 设置POST请求的头部信息
	xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

	// 设置请求完成的回调函数
	xhr.onreadystatechange = function () {
		if (xhr.readyState === 4) {
			if (xhr.status === 200) {
				// 请求成功，解析JSON响应
				try {
					var response = JSON.parse(xhr.responseText);
					console.log(response); // 打印整个响应对象，用于调试

					// 根据code的值来判断请求的结果
					if (response.code == 10000) {
						sendVerificationCode();
						if (response.data && response.data.code) {
							document.querySelector('input[name="code"]').value = response.data.code;
						}
						alert("发送成功");
					} else {
						// code不为0，表示有错误发生
						alert(response.message); // 假设response对象中有一个message字段包含错误信息
					}
				} catch (e) {
					// JSON解析失败，可能是响应内容不是有效的JSON格式
					alert("解析JSON失败：" + e.message);
				}
			} else {
				// 请求失败，状态码不是200
				alert("请求失败，状态码：" + xhr.status);
			}
		}
	};

	// 发送请求，对于POST请求，需要传递数据
	xhr.send("type=send&mobile=" + encodeURIComponent(mobile));
}

function onCopyInfoContnet(e) {
    e.preventDefault()
    const value = document.querySelector('#info-content').innerText
    
    let tempInput = document.createElement('input');
    // 将输入元素添加到页面中
    document.body.appendChild(tempInput);
    // 将需要复制的文本设置为 input 的 value
    tempInput.value = value;
    tempInput.select();
    tempInput.setSelectionRange(0, 99999); // 对于移动设备
    // 执行复制操作
    document.execCommand('copy');
    // 移除临时 input 元素
    document.body.removeChild(tempInput);
    alert('复制成功！')
}


function onToogleAuthAccount() {
    const btn = document.querySelector('#toggle-auth-account')
    const authAccount = document.querySelector('#auth-account')
    document.querySelector("#cdkey").innerHTML = ''
    if (btn.checked) {
        authAccount.style.display = "block"
    } else {
        authAccount.style.display = "none"
    }
}


function cancel() {
    console.log(1)
// 	window.history.go(-1);
}

setTimeout(function () {
	showParts(0);
}, 100);
