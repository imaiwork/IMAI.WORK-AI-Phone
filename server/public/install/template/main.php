<?php !defined('install') && exit(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>安装协议</title>
    <link
        rel="stylesheet"
        type="text/css"
        href="https://www.layuicdn.com/layui/css/layui.css" />
    <link rel="stylesheet" type="text/css" href="./css/mounted.css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background-image: url("./images/bg.png");
            background-size: 100% 100%;
            background-repeat: no-repeat;
            font-family: "微软雅黑,Microsoft Yahei,LiHei Pro,Hiragino Sans,GBHelvetica Neue,Helvetica,Arial,PingFang SC,WenQuanYi Micro Hei,sans-serif";
        }
    </style>
</head>

<body>
    <div>
        <header
            class="h-[90px] bg-white flex justify-between items-center px-4">
            <img
                src="./images/logo.png"
                class="h-7" />
            <h2 class="text-[22px] font-bold">AI数字员工系统安装向导</h2>
            <div class="flex items-center gap-2">
                <span class="text-[15px]">让人做更有价值的事 </span>
            </div>
        </header>
        <main class="w-[790px] mx-auto">
            <form method="post" action="#" name="main_form">
                <div
                    class="rounded-[5px] h-[65px] flex items-center my-[20px] bg-white">
                    <?php if ($step == '1') { ?>
                        <h2
                            class="text-[16px] font-bold text-[#3e84e9] w-full text-center">
                            AI数字员工系统使用协议
                        </h2>
                        <?php ?>
                    <?php } else { ?>
                        <div class="flex items-center justify-center gap-2 w-full">
                            <div class="flex items-center gap-2">
                                <?php if ($step == '2') { ?>
                                    <img
                                        src="./images/success.png"
                                        class="w-[25px] h-[25px]" />
                                    <?php ?>
                                <?php } else { ?>
                                    <img
                                        src="./images/success_gray.png"
                                        class="w-[25px] h-[25px]" />
                                <?php } ?>
                                <div class="text-[14px] font-bold">
                                    检查安装环境
                                </div>
                            </div>
                            <div class="w-[8%] h-[2px] bg-[#d1d1d1]"></div>
                            <div class="flex items-center gap-2"></div>
                            <?php if ($step == '3') { ?>
                                <img
                                    src="./images/success.png"
                                    class="w-[25px] h-[25px]" />
                                <?php ?>
                            <?php } else { ?>
                                <img
                                    src="./images/success_gray.png"
                                    class="w-[25px] h-[25px]" />
                            <?php } ?>
                            <div class="text-[14px] font-bold">创建数据库</div>
                            <div class="w-[8%] h-[2px] bg-[#d1d1d1]"></div>
                            <div class="flex items-center gap-2">
                                <?php if (in_array($step, ['4', "5"])) { ?>
                                    <img
                                        src="./images/success.png"
                                        class="w-[25px] h-[25px]" />
                                    <?php ?>
                                <?php } else { ?>
                                    <img
                                        src="./images/success_gray.png"
                                        class="w-[25px] h-[25px]" />
                                <?php } ?>
                                <div class="text-[14px] font-bold">安装成功</div>
                            </div>
                        </div>
                    <?php } ?>
                </div>

                <div class="bg-white rounded-[10px] p-5">

                    <!-- 阅读许可 -->
                    <?php if ($step == '1') { ?>
                        <div class="">
                            <div class="min-h-[493px] w-full rounded-[10px]">
                                <div
                                    class="max-h-[calc(100vh-457px)] overflow-y-auto">
                                    <div class="text-center font-bold text-[18px]">
                                        AI数字员工系统使用协议
                                    </div>
                                    <div class="text-[#666] mt-4">
                                        <div class="content leading-[28px]">
                                            <p class="mt-4">
                                                AI获客系统在此特别提醒您（用户）在订阅使用AI获客系统软件系统（以下简称“应用”）之前，请认真阅读本《AI获客系统应用授权协议》（以下简称“协议”），确保您充分理解本协议中各条款。请您审慎阅读并选择接受或不接受本协议。除非您接受本协议所有条款，否则您无权注册、登录、购买或使用本协议所涉服务。您的注册、登录、购买、使用等行为将视为对本协议的接受，并同意接受本协议各项条款的约束。
                                            </p>
                                            <p class="mt-4">
                                                本协议约定AI获客系统官网与用户之间的权利义务。“用户”是指注册、登录、订阅或使用AI获客系统应用的个人或企业。本协议可由AI获客系统官网随时更新，更新后的协议条款一旦公布即代替原来的协议条款，不再另行通知，用户可在本网站查阅最新版协议条款。在AI获客系统官网修改协议条款后，如果用户不接受修改后的条款，请立即停止使用AI获客系统官网提供的服务，用户继续使用AI获客系统官网提供的服务将被视为接受修改后的协议。
                                            </p>
                                            <h3 class="mt-4">一、订阅应用</h3>
                                            <p class="mt-4">
                                                1、用户可以在AI获客系统官网在线订阅AI获客系统付费应用，购买时需提前注册好AI获客系统官网账号。
                                                <br />
                                                2、用户以AI获客系统官网允许的支付方式订阅AI获客系统付费应用时，用户应当是具备完全民事权利能力和完全民事行为能力的自然人、法人或其他组织。若用户不具备前述主体资格，则用户及用户的监护人应承担因此而导致的一切后果，同时AI获客系统官网将保留追究用户及其监护人民事、刑事责任等权利，且AI获客系统官网有权注销(永久冻结)用户的AI获客系统官网帐号，并有权向用户及用户的监护人索赔。
                                                <br />
                                                3、用户应当在订阅使用AI获客系统付费应用之前认真阅读全部协议内容。用户确认AI获客系统官网对协议中所含免除或限制其责任的条款已尽提示、说明义务，用户同意此等条款，用户如对协议内容有任何异议的，应向AI获客系统官网咨询。但无论用户事实上是否在订阅或使用AI获客系统付费应用之前认真阅读了本协议内容，只要用户订阅或使用AI获客系统付费应用，即与AI获客系统官网缔结了本协议，本协议即对用户产生约束，届时用户不应以未阅读本协议的内容或者未获得AI获客系统官网对用户问询的解答等理由，主张本协议无效或要求撤销本协议。
                                                <br />
                                                4、用户承诺接受并遵守本协议的约定。如果用户不同意本协议的约定，应立即停止购买使用AI获客系统付费应用。
                                                <br />
                                                5、AI获客系统官网有权根据需要不时地制订、修改本协议或各类规则，并以公示的方式进行公告，不再单独通知用户。变更后的协议和规则一经公布后，立即自动生效。如用户不同意相关变更，应当立即停止使用AI获客系统付费应用。用户继续订阅或使用AI获客系统付费应用，即表示用户接受经修订的协议。
                                                <br />
                                            </p>

                                            <h3 class="mt-4">二、应用下载</h3>
                                            <p class="mt-4">
                                                1、用户可以在AI获客系统官网在线下载AI获客系统应用。
                                                <br />
                                                2、AI获客系统付费应用需要在注册登录且订阅后才可以下载安装使用。
                                                <br />
                                            </p>

                                            <h3 class="mt-4">三、应用使用</h3>
                                            <p class="mt-4">
                                                1、AI获客系统应用禁止在各类平台以任何形式（包括二次修改后）进行二次分发（出售）。<br />
                                                2、基于AI获客系统应用从事的一切商业行业和本站无关。<br />
                                                3、AI获客系统应用禁止分享、复制、转售和传播。<br />
                                                4、AI获客系统应用只支持中国大陆及港澳台地区安装使用。<br />
                                                5、AI获客系统付费应用务必正确录入授权主体信息后再进行使用。<br />
                                                6、用户不得利用AI获客系统应用制作、上载、复制、发布、传播如下法律、法规和政策禁止的内容：<br />
                                                (1) 反对宪法所确定的基本原则的；<br />
                                                (2)
                                                危害国家安全，泄露国家秘密，颠覆国家政权，破坏国家统一的；<br />
                                                (3) 损害国家荣誉和利益的；<br />
                                                (4)
                                                煽动民族仇恨、民族歧视，破坏民族团结的；<br />
                                                (5)
                                                破坏国家宗教政策，宣扬邪教和封建迷信的；<br />
                                                (6)
                                                散布谣言，扰乱社会秩序，破坏社会稳定的；<br />
                                                (7)
                                                散布淫秽、色情、赌博、暴力、凶杀、恐怖或者教唆犯罪的；<br />
                                                (8)
                                                侮辱或者诽谤他人，侵害他人合法权益的；<br />
                                                (9)
                                                不遵守法律法规底线、社会主义制度底线、国家利益底线、公民合法权益底线、社会公共秩序底线、道德风尚底线和信息真实性底线的“七条底线”要求的；<br />
                                                (10)
                                                含有法律、行政法规禁止的其他内容的信息。<br />

                                                如果用户违反相关上述相关使用条例，AI获客系统官网有权利收回用户订阅的付费应用和用户在AI获客系统官网注册的账号，如果有违反法律、法规和政策禁用的内容进行使用的，AI获客系统官网有权向公安机关举报并配合公安机关提供用户相关隐私个人信息。
                                            </p>

                                            <h3 class="mt-4">四、应用更新</h3>
                                            <p class="mt-4">
                                                1、在更新AI获客系统应用到最新版本时，请做好当前应用版本的整站备份，AI获客系统不对应用更新升级造成的损失承担任何责任。<br />
                                                2、如因AI获客系统应用下架造成的应用无法更新，AI获客系统不承担任何责任。<br />
                                                3、如因AI获客系统应用开发商放弃更新应用造成的无法更新，AI获客系统不承担任何责任。<br />
                                            </p>

                                            <h3 class="mt-4">五、应用价格</h3>
                                            <p class="mt-4">
                                                1、应用金额以最终结算价格为准，已售出的付费应用不做任何差价补偿。<br />
                                                2、如果利用系统漏洞或用户使用特殊手段以低价或免费的形式购买的应用，AI获客系统有权收回售出的应用。<br />
                                            </p>

                                            <h3 class="mt-4">六、应用授权</h3>
                                            <p class="mt-4">
                                                1、AI获客系统付费应用可用于个人或企业自营网站应用，可用于外包开发，禁止二次转售应用源码。<br />
                                            </p>

                                            <h3 class="mt-4">七、退款和转让</h3>
                                            <p class="mt-4">
                                                1、AI获客系统付费应用因提供全部源码，且源码可以拷贝，购买后不提供任何原因退款，请在购买前慎重考虑和仔细阅读此协议。<br />
                                                2、AI获客系统付费应用如果价格发生变动，此前购买的应用不提供任何的补偿或退款。<br />
                                                3、AI获客系统付费应用禁止以任何形式进行转让和出售。<br />
                                            </p>

                                            <h3 class="mt-4">八、知识产权声明</h3>
                                            <p class="mt-4">
                                                1、AI获客系统应用源代码所有权和著作权归应用开发商所有。<br />
                                                2、除另有特别声明外，AI获客系统应用所依托的代码、文字、图片等著作权、专利权及其他知识产权均归其开发商所有。<br />
                                            </p>

                                            <h3 class="mt-4">九、法律责任</h3>
                                            <p class="mt-4">
                                                1、如果AI获客系统官网发现或收到他人举报或投诉用户违反本协议约定的，AI获客系统官网有权不经通知随时对相关内容，包括但不限于用户资料、聊天记录进行审查、删除，并视情节轻重对违规帐号处以包括但不限于警告、帐号封禁、设备封禁、功能封禁的处罚。<br />
                                                2、用户理解并同意，AI获客系统官网有权依合理判断对违反有关法律法规或本协议规定的行为进行处罚，对违法违规的任何用户采取适当的法律行动，并依据法律法规保存有关信息向有关部门报告等，用户应承担由此而产生的一切法律责任。<br />
                                                3、用户理解并同意，因用户违反本协议约定，导致或产生的任何第三方主张的任何索赔、要求或损失，包括合理的律师费，用户应当赔偿AI获客系统官网与合作公司、关联公司，并使之免受损害。<br />
                                            </p>

                                            <h3 class="mt-4">
                                                十、不可抗力及其他免责事由
                                            </h3>
                                            <p class="mt-4">
                                                1、用户理解并确认，在使用本服务的过程中，可能会遇到不可抗力等风险因素，使本服务发生中断。不可抗力是指不能预见、不能克服并不能避免且对一方或双方造成重大影响的客观事件，包括但不限于自然灾害如洪水、地震、瘟疫流行和风暴等以及社会事件如战争、动乱、政府行为等。出现上述情况时，AI获客系统官网将努力在第一时间与相关单位配合，及时进行修复，但是由此给用户或第三方造成的损失，AI获客系统官网及合作单位在法律允许的范围内免责。<br />
                                                2、本服务同大多数互联网服务一样，受包括但不限于用户原因、网络服务质量、社会环境等因素的差异影响，可能受到各种安全问题的侵扰，如他人利用用户的资料，造成现实生活中的骚扰；用户下载安装的其它软件或访问的其他网站中含有“特洛伊木马”等病毒，威胁到用户的计算机信息和数据的安全，继而影响本服务的正常使用等等。用户应加强信息安全及使用者资料的保护意识，要注意加强帐号保护，以免遭致损失和骚扰。<br />
                                                3、用户理解并确认，本服务存在因不可抗力、计算机病毒或黑客攻击、系统不稳定、用户所在位置、用户关机以及其他任何技术、互联网络、通信线路原因等造成的服务中断或不能满足用户要求的风险，因此导致的用户或第三方任何损失，AI获客系统官网不承担任何责任。<br />
                                                4、用户理解并确认，在使用本服务过程中存在来自任何他人的包括误导性的、欺骗性的、威胁性的、诽谤性的、令人反感的或非法的信息，或侵犯他人权利的匿名或冒名的信息，以及伴随该等信息的行为，因此导致的用户或第三方的任何损失，AI获客系统官网不承担任何责任。<br />
                                                5、用户理解并确认，AI获客系统官网需要定期或不定期地对AI获客系统官网平台或相关的设备进行检修或者维护，如因此类情况而造成服务在合理时间内的中断，AI获客系统官网无需为此承担任何责任。<br />
                                                6、AI获客系统官网依据法律法规、本协议约定获得处理违法违规或违约内容的权利，该权利不构成AI获客系统官网的义务或承诺，AI获客系统官网不能保证及时发现违法违规或违约行为或进行相应处理。<br />
                                                7、用户理解并确认，对于AI获客系统官网向用户提供的下列产品或者服务的质量缺陷及其引发的任何损失，AI获客系统官网无需承担任何责任：<br />
                                                (1)
                                                AI获客系统官网向用户免费提供的服务；<br />
                                                (2)
                                                AI获客系统官网向用户赠送的任何产品或者服务。<br />
                                                8、在任何情况下，AI获客系统官网均不对任何间接性、后果性、惩罚性、偶然性、特殊性或刑罚性的损害，包括因用户使用AI获客系统官网或本服务而遭受的利润损失，承担责任（即使AI获客系统官网已被告知该等损失的可能性亦然）。尽管本协议中可能含有相悖的规定，AI获客系统官网对用户承担的全部责任，无论因何原因或何种行为方式，始终不超过用户因使用AI获客系统官网提供的服务而支付给AI获客系统官网的费用(如有)。<br />
                                            </p>

                                            <h3 class="mt-4">
                                                十一、服务的变更、中断、终止
                                            </h3>
                                            <p class="mt-4">
                                                1、鉴于网络服务的特殊性，用户同意AI获客系统官网有权随时变更、中断或终止部分或全部的服务（包括收费服务）。AI获客系统官网变更、中断或终止的服务，AI获客系统官网应当在变更、中断或终止之前通知用户。<br />
                                                2、如发生下列任何一种情形，AI获客系统官网有权变更、中断或终止向用户提供的免费服务或收费服务，而无需对用户或任何第三方承担任何责任：<br />
                                                (1)
                                                根据法律规定用户应提交真实信息，而用户提供的个人资料不真实、或与注册时信息不一致又未能提供合理证明；<br />
                                                (2)
                                                用户违反相关法律法规或本协议的约定；<br />
                                                (3)
                                                按照法律规定或有权机关的要求；<br />
                                                (4)
                                                出于安全的原因或其他必要的情形。<br />
                                            </p>

                                            <h3 class="mt-4">十二、其他</h3>
                                            <p class="mt-4">
                                                1、AI获客系统官网郑重提醒用户注意本协议中免除AI获客系统官网责任和限制用户权利的条款，请用户仔细阅读，自主考虑风险。未成年人应在法定监护人的陪同下阅读本协议。<br />
                                                2、本协议的效力、解释及纠纷的解决，适用于中华人民共和国法律。若用户和AI获客系统官网之间发生任何纠纷或争议，首先应友好协商解决，协商不成的，用户同意将纠纷或争议提交AI获客系统官网住所地有管辖权的人民法院管辖。<br />
                                                3、本协议的任何条款无论因何种原因无效或不具可执行性，其余条款仍有效，对双方具有约束力。<br />
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-[30px]">
                                <div class="flex justify-center">
                                    <button
                                        class="h-[40px] w-[120px] rounded-[40px] bg-[#3e84e9] text-white accept-btn"
                                        onclick="goStep(<?php echo $nextStep ?>)">
                                        我同意
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php }  ?>


                    <!-- 检查信息 -->
                    <?php if ($step == '2') { ?>
                        <div class="text-[16px] font-bold">检查安装环境</div>
                        <div
                            class="px-[30px] max-h-[calc(100vh-500px)] min-h-[500px] overflow-y-auto">
                            <div class="mt-[40px]">
                                <div
                                    class="bg-[#E5E5E5] py-[8px] px-[16px] rounded-[5px] mb-2">
                                    服务器信息
                                </div>
                                <table
                                    class="w-full border border-[#e1e1e1] rounded-[5px] overflow-hidden border-separate">
                                    <thead>
                                        <tr class="h-[34px] bg-[#f7f7f7]">
                                            <th>检查项</th>
                                            <th>当前环境</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-center">
                                        <tr class="h-[34px]">
                                            <td>操作系统</td>
                                            <td><?php echo PHP_OS ?></td>
                                        </tr>
                                        <tr class="h-[34px]">
                                            <td>web服务器环境</td>
                                            <td>
                                                <?php echo $_SERVER['SERVER_SOFTWARE']; ?>
                                            </td>
                                        </tr>
                                        <tr class="h-[34px]">
                                            <td>PHP版本</td>
                                            <td>
                                                <?php echo @phpversion(); ?>
                                            </td>
                                        </tr>
                                        <tr class="h-[34px]">
                                            <td>程序安装目录</td>
                                            <td>
                                                <?php echo realpath(__DIR__ . '/../../../'); ?>
                                            </td>
                                        </tr>
                                        <tr class="h-[34px]">
                                            <td>磁盘空间</td>
                                            <td>
                                                <?php echo $modelInstall->freeDiskSpace(realpath(__DIR__
                                                    . '../../../')) ?>
                                            </td>
                                        </tr>
                                        <tr class="h-[34px]">
                                            <td>上传限制</td>
                                            <?php if (ini_get('file_uploads')): ?>
                                                <td>
                                                    <?php echo ini_get('upload_max_filesize'); ?>
                                                </td>
                                            <?php else: ?>
                                                <td>禁止上传</td>
                                            <?php endif; ?>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div
                                class="bg-[#eef4ff] py-[15px] px-[24px] text-[#2C85EA] mt-4">
                                PHP环境要求必须满足下列所有条件，否则系统或系统部分功能将无法使用。
                            </div>
                            <div class="mt-4">
                                <div
                                    class="bg-[#E5E5E5] py-[8px] px-[16px] rounded-[5px] mb-2">
                                    PHP环境要求
                                </div>
                                <table
                                    class="w-full border border-[#e1e1e1] rounded-[5px] overflow-hidden border-separate">
                                    <thead>
                                        <tr class="h-[34px] bg-[#f7f7f7]">
                                            <th>检查项</th>
                                            <th>当前环境</th>
                                            <th>建议</th>
                                            <th>当前状态</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="h-[34px] text-center">
                                            <td>PHP版本</td>
                                            <td>大于8.0</td>
                                            <td>建议使用PHP8.0.8版本</td>
                                            <?php echo $modelInstall->correctOrFail($modelInstall->checkPHP()) ?>
                                        </tr>
                                        <tr class="h-[34px] text-center">
                                            <td>PDO_MYSQL</td>
                                            <td>支持</td>
                                            <td>(强烈建议支持)</td>
                                            <?php echo $modelInstall->correctOrFail($modelInstall->checkPDOMySQL())
                                            ?>
                                        </tr>
                                        <tr class="h-[34px] text-center">
                                            <td>allow_url_fopen</td>
                                            <td>支持</td>
                                            <td>(建议支持cURL)</td>
                                            <?php echo $modelInstall->correctOrFail($modelInstall->checkCurl())
                                            ?>
                                        </tr>
                                        <tr class="h-[34px] text-center">
                                            <td>GD2</td>
                                            <td>支持</td>
                                            <td>支持</td>
                                            <?php echo $modelInstall->correctOrFail($modelInstall->checkGd2())
                                            ?>
                                        </tr>
                                        <tr class="h-[34px] text-center">
                                            <td>DOM</td>
                                            <td>支持</td>
                                            <td>支持</td>
                                            <?php echo $modelInstall->correctOrFail($modelInstall->checkDom())
                                            ?>
                                        </tr>
                                        <tr class="h-[34px] text-center">
                                            <td>fileinfo</td>
                                            <td>支持</td>
                                            <td>支持</td>
                                            <?php echo $modelInstall->correctOrFail($modelInstall->checkFileInfo())
                                            ?>
                                        </tr>
                                        <tr class="h-[34px] text-center">
                                            <td>session.auto_start</td>
                                            <td>支持</td>
                                            <td>支持</td>
                                            <?php echo $modelInstall->correctOrFail($modelInstall->checkSessionAutoStart())
                                            ?>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div
                                class="bg-[#eef4ff] py-[15px] px-[24px] text-[#2C85EA] mt-4">
                                系统要求AI获客系统安装目录下的runtime和upload必须可写，才能使用AI获客系统的所有功能。
                            </div>
                            <div class="mt-4">
                                <div
                                    class="bg-[#E5E5E5] py-[8px] px-[16px] rounded-[5px] mb-2">
                                    目录权限监测
                                </div>
                                <table
                                    class="w-full border border-[#e1e1e1] rounded-[5px] overflow-hidden border-separate">
                                    <thead>
                                        <tr class="h-[34px] bg-[#f7f7f7]">
                                            <th>检查项</th>
                                            <th>当前环境</th>
                                            <th>建议</th>
                                            <th>当前状态</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="h-[34px] text-center">
                                            <td>/runtime</td>
                                            <td>runtime目录可写</td>
                                            <td>
                                                <?php if (
                                                    $modelInstall->checkDirWrite('runtime')
                                                    == 'fail'
                                                ) echo
                                                '请给runtime目录权限，若目录不存在先新建';
                                                ?>
                                            </td>
                                            <?php echo $modelInstall->correctOrFail($modelInstall->checkDirWrite('runtime'))
                                            ?>
                                        </tr>
                                        <tr class="h-[34px] text-center">
                                            <td>/public/uploads</td>
                                            <td>uploads目录可写</td>
                                            <td>
                                                <?php if (
                                                    $modelInstall->checkDirWrite('public/uploads')
                                                    == 'fail'
                                                ) echo
                                                '请给public/uploads目录权限，若目录不存在先新建';
                                                ?>
                                            </td>
                                            <?php echo $modelInstall->correctOrFail($modelInstall->checkDirWrite('public/uploads'))
                                            ?>
                                        </tr>
                                        <tr class="h-[34px] text-center">
                                            <td>/public/admin</td>
                                            <td>admin目录可写</td>
                                            <td>
                                                <?php if (
                                                    $modelInstall->checkDirWrite('public/uploads')
                                                    == 'fail'
                                                ) echo
                                                '请给public/admin目录权限，若目录不存在先新建';
                                                ?>
                                            </td>
                                            <?php echo $modelInstall->correctOrFail($modelInstall->checkDirWrite('public/admin'))
                                            ?>
                                        </tr>
                                        <tr class="h-[34px] text-center">
                                            <td>../config</td>
                                            <td>config目录可写</td>
                                            <td>
                                                <?php if (
                                                    $modelInstall->checkDirWrite('public/uploads')
                                                    == 'fail'
                                                ) echo
                                                '请给public/admin目录权限，若目录不存在先新建';
                                                ?>
                                            </td>
                                            <?php echo $modelInstall->correctOrFail($modelInstall->checkDirWrite('public/admin'))
                                            ?>
                                        </tr>
                                        <tr class="h-[34px] text-center">
                                            <td>../.env</td>
                                            <td>.env文件可写</td>
                                            <td>
                                                <?php if (
                                                    $modelInstall->checkDirWrite('public/uploads')
                                                    == 'fail'
                                                ) echo
                                                '请给public/admin目录权限，若目录不存在先新建';
                                                ?>
                                            </td>
                                            <?php echo $modelInstall->correctOrFail($modelInstall->checkDirWrite('public/admin'))
                                            ?>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    <?php } ?>

                    <!-- 数据库设置 -->
                    <?php if ($step == '3') { ?>
                        <div class="text-[16px] font-bold">创建数据库</div>
                        <div class="mounted-content-item show">
                            <div class="mounted-item">
                                <div class="content-header">
                                    数据库选项
                                </div>
                                <div class="content-form">

                                    <div class="form-box-item">
                                        <div class="form-desc">
                                            数据库主机
                                        </div>
                                        <div>
                                            <input type="text" name="host" value="<?= $post['host'] ?>" />
                                        </div>
                                    </div>
                                    <div class="form-box-item">
                                        <div class="form-desc">
                                            端口号
                                        </div>
                                        <div>
                                            <input type="text" name="port" value="<?= $post['port'] ?>" />
                                        </div>
                                    </div>
                                    <div class="form-box-item">
                                        <div class="form-desc">
                                            数据库用户
                                        </div>
                                        <div>
                                            <input type="text" name="user" value="<?= $post['user'] ?>" />
                                        </div>
                                    </div>
                                    <div class="form-box-item">
                                        <div class="form-desc">
                                            数据库密码
                                        </div>
                                        <div>
                                            <input type="text" name="password" value="<?= $post['password'] ?>" />
                                        </div>
                                    </div>
                                    <div class="form-box-item">
                                        <div class="form-desc">
                                            数据库名称
                                        </div>
                                        <div>
                                            <input type="text" name="name" value="<?= $post['name'] ?>" />
                                        </div>
                                    </div>
                                    <div class="form-box-item">
                                        <div class="form-desc">
                                            数据表前缀
                                        </div>
                                        <div>
                                            <input type="text" name="prefix" value="<?= $post['prefix'] ?>" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mounted-item">
                                <div class="content-header mt16">
                                    管理选项
                                </div>
                                <div class="content-form">

                                    <div class="form-box-item">
                                        <div class="form-desc">
                                            管理员账号
                                        </div>
                                        <div>
                                            <input type="text" name="admin_user" value="<?= $post['admin_user'] ?>" />
                                        </div>
                                    </div>
                                    <div class="form-box-item">
                                        <div class="form-desc">
                                            管理员密码
                                        </div>
                                        <div>
                                            <input type="password" name="admin_password"
                                                value="<?= $post['admin_password'] ?>" />
                                        </div>
                                    </div>
                                    <div class="form-box-item">
                                        <div class="form-desc">
                                            确认密码
                                        </div>
                                        <div>
                                            <input type="password" name="admin_confirm_password"
                                                value="<?= $post['admin_confirm_password'] ?>" />
                                        </div>
                                    </div>
                                    <div class="form-box-item">
                                        <div class="form-desc">
                                            手机号
                                        </div>
                                        <div>
                                            <input type="mobile" name="mobile" id="mobile"
                                                value="<?= $post['mobile'] ?>" />
                                        </div>
                                    </div>
                                    <div class="form-box-item">
                                        <div class="form-desc">
                                            验证码
                                        </div>
                                        <div class="mt-4">
                                            <div class="flex items-center gap-2">
                                                <input type="code" name="code"
                                                    value="<?= $post['code'] ?>" />
    
                                                <button class="accept-btn code-btn h-full py-[5px] rounded-md" onclick="sendCode(event)">
                                                    发送
                                                </button>
                                            </div>
                                            <div class="text-xs mt-2">
                                                用户找回注册账号和通讯秘钥，非常关键的信息
                                            </div>
                                        </div>
                                    </div>
                                    <!--                                    <div class="form-box-check">-->
                                    <!--                                        <div class="form-desc"></div>-->
                                    <!--                                        <div style="display: flex;align-items: center;">-->
                                    <!--                                            <input type="checkbox" name="import_test_data"-->
                                    <!--                                                   --><?php //if ($post['import_test_data'] == 'on'): 
                                                                                                ?><!--checked--><?php //endif; 
                                                                                                                        ?>
                                    <!--                                                   title="导入测试数据"/>-->
                                    <!--                                            <div style="color: #666666;">&nbsp;导入测试数据</div>-->
                                    <!--                                        </div>-->
                                    <!--                                    </div>-->
                                </div>
                            </div>

                          
                            <div class="mounted-item">
                                <div class="content-header">
                                    PostgreSQL配置项
                                </div>
                                <div class="content-form">

                                    <div class="form-box-item">
                                        <div class="form-desc">
                                            数据库主机
                                        </div>
                                        <div>
                                            <input type="text" name="pg_host" value="<?= $post['pg_host'] ?>" />
                                        </div>
                                    </div>
                                    <div class="form-box-item">
                                        <div class="form-desc">
                                            端口号
                                        </div>
                                        <div>
                                            <input type="text" name="pg_port" value="<?= $post['pg_port'] ?>" />
                                        </div>
                                    </div>
                                    <div class="form-box-item">
                                        <div class="form-desc">
                                            数据库用户
                                        </div>
                                        <div>
                                            <input type="text" name="pg_user" value="<?= $post['pg_user'] ?>" />
                                        </div>
                                    </div>
                                    <div class="form-box-item">
                                        <div class="form-desc">
                                            数据库密码
                                        </div>
                                        <div>
                                            <input type="text" name="pg_password" value="<?= $post['pg_password'] ?>" />
                                        </div>
                                    </div>
                                    <div class="form-box-item">
                                        <div class="form-desc">
                                            数据库名称
                                        </div>
                                        <div>
                                            <input type="text" name="pg_name" value="<?= $post['pg_name'] ?>" />
                                        </div>
                                    </div>
                                    <div class="form-box-item">
                                        <div class="form-desc">
                                            数据表前缀
                                        </div>
                                        <div>
                                            <input type="text" name="pg_prefix" value="<?= $post['pg_prefix'] ?>" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            

                          
                            <!-- <div class="mounted-item">
                                <div class="content-header mt16">
                                    授权
                                </div>
                                <div class="content-form">
                                    <div class="form-box-item">
                                         <div class="form-desc">
                                            是否有卡号
                                        </div>
                                        <div>
                                            <label for="toggle-auth-account" class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" checked id="toggle-auth-account" class="sr-only peer" onclick="onToogleAuthAccount()">
                                            <div class="w-11 h-6 bg-gray-200 rounded-full peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                          </label>
                                        </div>
                                    </div>
                                    <div id="auth-account">
                                         <div class="form-box-item">
                                            <div class="form-desc">
                                                授权卡号
                                            </div>
                                            <div>
                                                <input id="cdkey"  type="text" name="cdkey" value="<?= $post['cdkey'] ?>" />
                                            </div>
                                        </div>
                                    </div>
                                   
                                    <div class="mt-2 ml-[74px] text-[#FF3014]">
                                        <a href="https://shop.AI获客系统/static/html/pc.html" target="_blank">去购买授权卡号</a>
                                    </div>
                                </div>
                            </div> -->
                        </div>
                    <?php } ?>

                    <!-- 安装中 -->
                    <?php if ($step == '4' or $step == '5') { ?>
                        <div class="mounted-content-item show">
                            <?php if ($step == '4') { ?>
                                <div id="mounting">
                                    <div class="content-header">
                                        正在安装中
                                    </div>
                                    <div class="mounting-container " id="install_message">
                                        <?php if (count($successTables) > 0): ?>
                                            <p style="margin-bottom: 4px;">成功创建数据库：<?= $post['name'] ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php } ?>

                            <?php if ($step == '5') { ?>
                                <div class="show" id="mounting-success">
                                    <div class="success-content">
                                        <div style="width: 48px;height: 48px;">
                                            <img src="./images/icon_mountSuccess.png" />
                                        </div>
                                        <div class="mt16 result">安装完成，进入管理后台</div>
                                        <div style="margin-top: 5px;font-size:14px;">版本号：2.7.7</div>
                                        <div class="tips rounded-lg">
                                            为了您站点的安全，安装完成后即可将网站根目录的“public”下的“install”文件夹删除，或者根目录下创建install.lock文件防止重复安装。
                                        </div>
                                        <div class="rounded-lg py-4 px-10 bg-[#F8F8F8] mt-4 w-[400px]" >
                                           <div id="info-content">
                                               <div>
                                               <div class="text-lg font-bold">
                                                   后台信息
                                               </div>
                                               <div class="mt-2">
                                                   <div>地址：<?= $address['admin'] ?></div>
                                                   <div>账号：<?= $post['admin_user'] ?></div>
                                                   <div>密码: <?= $post['admin_password'] ?></div>
                                               </div>
                                               </div>
                                               <div class="mt-4">
                                                   <div class="text-lg font-bold">
                                                       数据中台
                                                   </div>
                                                   <div class="mt-2">
                                                       <div>地址：<?= $address['ai'] ?></div>
                                                       <div>账号：<?= $post['mobile'] ?></div>
                                                       <div>密码: <?= $post['ai_password'] ?></div>
                                                   </div>
                                               </div>
                                           </div>
                                           <div class="flex justify-center w-full mt-2">
                                               <button class="bg-[#2C85EA] py-[7px] px-[35px] text-white rounded-lg" onclick="onCopyInfoContnet(event)">一键复制</button>
                                         </div>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    <?php } ?>

                    <div class="flex justify-center">
                        <?php if (in_array($step, ['2', "3"])) { ?>
                            <div class="item-btn-group show">
                                 <button class="cancel-btn" onclick="goStep(<?php echo $lastStep ?>)" style="padding: 7px 63px;margin-right: 16px">    
                                    上一步
                                </button>
                                <?php if ($modelInstall->getAllowNext()): ?>
                                    <button id="jinyon" class="accept-btn" onclick="goStep(<?php echo $nextStep ?>)" style="padding: 7px 63px;">
                                        继续
                                    </button>
                                <?php else: ?>
                                    <button class="accept-btn" onclick="goStep(<?php echo $step ?>)" style="padding: 7px 63px;">重新检查
                                    </button>
                                <?php endif; ?>
                            </div>
                        <?php } elseif ($step == "4") { ?>
                            <div class="item-btn-group show">
                                <button class="disabled-btn" disabled="disabled">
                                    <div class="layui-icon layui-icon-loading layui-anim layui-anim-rotate layui-anim-loop"></div>
                                    <div style="font-size: 14px;margin-left: 7px;">正在安装中...</div>
                                </button>
                            </div>
                        <?php } ?>
                    </div>
                </div>

            </form>


        </main>
        <footer
            class="text-center text-white my-[40px] font-bold text-[15px]">
            <div>
                受国家计算机软件著作权保护，未经授权不得进行商业行为，违者必究
            </div>
            <div class="mt-3">
                ©2025 AI数字员工让人做更意义的事情
            </div>
        </footer>
    </div>
</body>
<script src="https://www.layuicdn.com/layui/layui.js"></script>
<?php if (
    count($successTables) >
    0
): ?>
    <script>
        var successTables = eval(<?= json_encode($successTables) ?>);
    </script>
<?php endif; ?>
<script src="./js/mounted.js"></script>

</html>
<?php if ($message != ''): ?>
    <script>
        alert("<?= $message; ?>");
    </script>
<?php endif; ?>