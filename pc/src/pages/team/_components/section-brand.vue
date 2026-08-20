<template>
    <div>
        <!-- 未开通 OEM：引导解锁 -->
        <section v-if="!oemActive" class="panel min-h-[420px] flex items-center justify-center">
            <div class="max-w-[480px] text-center px-2 pt-3 pb-2">
                <div
                    class="w-14 h-14 mx-auto mb-4 rounded-2xl grid place-items-center text-primary bg-primary/10">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="w-7 h-7">
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75M6.75 21h10.5a2.25 2.25 0 002.25-2.25v-6a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6A2.25 2.25 0 006.75 21z" />
                    </svg>
                </div>

                <template v-if="oemStatus === 1">
                    <h2 class="text-xl font-[1000] text-slate-900 text-balance">申请已提交，等待站长审核</h2>
                    <p class="mt-2 text-sm leading-relaxed text-slate-500 text-pretty">
                        预缴算力
                        <b class="text-amber-500">{{ info?.oem_price }}</b>
                        已扣除。审核通过后自动解锁；若被拒绝将全额退回。
                    </p>
                    <ol class="mt-[22px] mx-auto max-w-[300px] grid gap-2.5 text-left">
                        <li class="flex items-center gap-2.5 text-[13px] font-bold text-emerald-600">
                            <i
                                class="not-italic w-[22px] h-[22px] rounded-full grid place-items-center text-[11px] shrink-0 bg-emerald-500/15 text-emerald-600"
                                >✓</i
                            ><span>提交 OEM 申请</span>
                        </li>
                        <li class="flex items-center gap-2.5 text-[13px] font-bold text-slate-900">
                            <i
                                class="not-italic w-[22px] h-[22px] rounded-full grid place-items-center text-[11px] shrink-0 bg-primary/10 text-primary"
                                >2</i
                            ><span>站长审核中</span>
                        </li>
                        <li class="flex items-center gap-2.5 text-[13px] font-bold text-slate-400">
                            <i
                                class="not-italic w-[22px] h-[22px] rounded-full grid place-items-center text-[11px] shrink-0 bg-slate-100 text-slate-400"
                                >3</i
                            ><span>配置站点品牌与小程序</span>
                        </li>
                    </ol>
                </template>
                <template v-else>
                    <h2 class="text-xl font-[1000] text-slate-900 text-balance">开通企业 OEM，用自己的品牌获客</h2>
                    <p class="mt-2 text-sm leading-relaxed text-slate-500 text-pretty">
                        访客通过你的域名 / 小程序进入时，看到的是你的品牌，注册用户归属你的团队。
                    </p>
                    <ul
                        class="mt-5 mx-auto max-w-[320px] text-left grid gap-2 text-[13px] font-semibold text-slate-700">
                        <li
                            v-for="b in lockBenefits"
                            :key="b"
                            class="relative pl-[18px] before:content-[''] before:absolute before:left-0 before:top-[7px] before:w-2 before:h-2 before:rounded-full before:bg-primary/35">
                            {{ b }}
                        </li>
                    </ul>
                    <ol class="mt-[22px] mx-auto max-w-[300px] grid gap-2.5 text-left">
                        <li class="flex items-center gap-2.5 text-[13px] font-bold text-slate-900">
                            <i
                                class="not-italic w-[22px] h-[22px] rounded-full grid place-items-center text-[11px] shrink-0 bg-primary/10 text-primary"
                                >1</i
                            ><span>提交申请并预缴算力</span>
                        </li>
                        <li class="flex items-center gap-2.5 text-[13px] font-bold text-slate-400">
                            <i
                                class="not-italic w-[22px] h-[22px] rounded-full grid place-items-center text-[11px] shrink-0 bg-slate-100 text-slate-400"
                                >2</i
                            ><span>站长审核通过</span>
                        </li>
                        <li class="flex items-center gap-2.5 text-[13px] font-bold text-slate-400">
                            <i
                                class="not-italic w-[22px] h-[22px] rounded-full grid place-items-center text-[11px] shrink-0 bg-slate-100 text-slate-400"
                                >3</i
                            ><span>按引导完成站点配置</span>
                        </li>
                    </ol>
                    <ElButton type="primary" size="large" class="!rounded-xl !px-10 mt-6" @click="showUpgrade = true">
                        申请开通 OEM
                    </ElButton>
                    <p class="mt-3 text-xs text-slate-400">应付算力 {{ info?.oem_price }}，提交后需站长审核</p>
                </template>
            </div>
        </section>

        <!-- 已开通 -->
        <template v-else>
            <!-- 开通进度：告诉用户先做什么 -->
            <div class="panel !py-4 !px-5 mb-5">
                <div class="flex flex-wrap items-baseline gap-x-4 gap-y-2 mb-3.5">
                    <span class="text-[13px] font-[1000] text-slate-900">开通进度</span>
                    <span class="text-xs text-slate-500">按顺序完成前两步后，站点即可对外展示你的品牌</span>
                </div>
                <div class="grid grid-cols-2 gap-2.5">
                    <button
                        v-for="s in setupSteps"
                        :key="s.key"
                        type="button"
                        class="flex items-center gap-3 text-left px-3.5 py-3 rounded-[14px] border transition-colors cursor-pointer"
                        :class="
                            brandTab === s.key
                                ? 'border-primary/45 bg-primary/[0.04] shadow-[0_0_0_3px_rgba(0,101,251,0.08)]'
                                : 'border-slate-200 bg-slate-50 hover:border-primary/35 hover:bg-white'
                        "
                        @click="brandTab = s.key">
                        <span
                            class="w-7 h-7 rounded-full grid place-items-center text-xs font-[1000] shrink-0"
                            :class="
                                s.done
                                    ? 'bg-emerald-500/15 text-emerald-600'
                                    : 'bg-primary/10 text-primary'
                            ">
                            <template v-if="s.done">✓</template>
                            <template v-else>{{ s.n }}</template>
                        </span>
                        <span class="min-w-0 flex flex-col gap-0.5">
                            <span class="text-[13px] font-extrabold text-slate-900">{{ s.title }}</span>
                            <span class="text-[11px] text-slate-500">{{ s.done ? s.doneText : s.todoText }}</span>
                        </span>
                    </button>
                </div>
            </div>

            <div class="mb-5">
                <div class="brand-tabs">
                    <button
                        v-for="t in BRAND_TABS"
                        :key="t.key"
                        type="button"
                        class="brand-tab"
                        :class="{ active: brandTab === t.key }"
                        @click="brandTab = t.key">
                        {{ t.short }}
                    </button>
                </div>
            </div>

            <!-- ① 站点外观 -->
            <section v-show="brandTab === 'site'" class="space-y-5">
                <div
                    class="flex gap-3.5 items-start px-[18px] py-4 rounded-2xl border border-primary/15 bg-gradient-to-br from-primary/[0.06] to-primary/[0.02]">
                    <div
                        class="w-8 h-8 rounded-[10px] grid place-items-center text-[13px] font-[1000] text-primary bg-white border border-primary/20 shrink-0">
                        1
                    </div>
                    <div class="min-w-0">
                        <div class="text-[15px] font-[1000] text-slate-900">先配置站点外观</div>
                        <p class="mt-1 text-[13px] leading-relaxed text-slate-600 max-w-[72ch]">
                            域名、标题和 LOGO 决定访客打开你站点时看到的样子。填好后点「保存」，再到下一步配置小程序。
                        </p>
                    </div>
                </div>

                <div class="panel">
                    <ElForm label-position="top" class="max-w-[540px]">
                        <div
                            class="mb-5 text-[13px] leading-relaxed text-slate-600 px-3.5 py-3 rounded-xl bg-amber-50 border border-amber-200">
                            <b class="text-amber-800 mr-1">域名怎么配？</b>
                            把你的域名（如
                            <code class="text-xs px-1.5 py-px rounded bg-amber-800/10 text-amber-800"
                                >team.example.com</code
                            >）CNAME / A 记录解析到本平台，再填到下方。未解析前访问会打不开。
                        </div>

                        <ElFormItem label="站点域名" required>
                            <ElInput v-model="tenant.domain" placeholder="例如 team.example.com" />
                        </ElFormItem>
                        <ElFormItem label="站点标题" required>
                            <ElInput v-model="tenant.brand.name" placeholder="显示在浏览器标签上的名称" />
                        </ElFormItem>

                        <div class="grid grid-cols-2 gap-4">
                            <ElFormItem label="站点 ICON" required>
                                <BrandUpload
                                    variant="icon"
                                    :preview="tenant.brand.web_logo"
                                    title="上传 ICON"
                                    hint="建议 64×64，标签页小图标"
                                    @success="onBrandIcon" />
                            </ElFormItem>
                            <ElFormItem label="站点 LOGO" required>
                                <BrandUpload
                                    variant="logo"
                                    :preview="tenant.brand.pc_logo"
                                    title="上传 LOGO"
                                    hint="建议 128×128，页头展示"
                                    @success="onBrandLogo" />
                            </ElFormItem>
                        </div>

                        <ElFormItem label="管理员联系二维码">
                            <p class="text-xs text-slate-500 -mt-1 mb-2 leading-snug">
                                站点用户充值时弹窗展示，方便他们加你微信。
                            </p>
                            <BrandUpload
                                variant="qr"
                                :preview="tenant.brand.admin_qr"
                                title="上传二维码"
                                hint="正方形清晰图"
                                @success="onBrandQr" />
                        </ElFormItem>

                        <ElFormItem label="备案号">
                            <div class="w-full">
                                <p class="text-xs text-slate-500 -mt-1 mb-2 leading-snug">
                                    展示在 PC 侧栏底部与小程序「我的」页脚，不填则不展示。
                                </p>
                                <ElInput
                                    v-model="tenant.brand.icp_number"
                                    maxlength="64"
                                    clearable
                                    placeholder="例如 京ICP备xxxxxxxx号-x" />
                            </div>
                        </ElFormItem>
                        <ElFormItem label="企业名称">
                            <div class="w-full">
                                <p class="text-xs text-slate-500 -mt-1 mb-2 leading-snug">
                                    展示在备案号下方，不填则不展示。
                                </p>
                                <ElInput
                                    v-model="tenant.brand.company_name"
                                    maxlength="64"
                                    clearable
                                    placeholder="例如 xx科技有限公司" />
                            </div>
                        </ElFormItem>

                        <div class="flex flex-wrap items-center gap-3 mt-2">
                            <ElButton type="primary" class="!rounded-xl" @click="saveBrandThen">保存站点外观</ElButton>
                            <ElButton class="!rounded-xl" @click="brandTab = 'mnp'">下一步：配置小程序</ElButton>
                        </div>
                    </ElForm>
                </div>
            </section>

            <!-- ② 微信小程序 -->
            <section v-show="brandTab === 'mnp'" class="space-y-5">
                <div
                    class="flex gap-3.5 items-start px-[18px] py-4 rounded-2xl border border-primary/15 bg-gradient-to-br from-primary/[0.06] to-primary/[0.02]">
                    <div
                        class="w-8 h-8 rounded-[10px] grid place-items-center text-[13px] font-[1000] text-primary bg-white border border-primary/20 shrink-0">
                        2
                    </div>
                    <div class="min-w-0">
                        <div class="text-[15px] font-[1000] text-slate-900">再配置微信小程序</div>
                        <p class="mt-1 text-[13px] leading-relaxed text-slate-600 max-w-[72ch]">
                            先在微信公众平台拿到 AppID /
                            密钥等凭证并保存；再上传代码包提交审核。两步都做完，用户才能扫码进你的小程序。
                        </p>
                    </div>
                </div>

                <!-- 阶段 1 -->
                <div class="panel">
                    <div class="flex gap-3 items-start">
                        <span
                            class="shrink-0 text-[11px] font-[1000] px-2 py-1 rounded-full mt-0.5"
                            :class="mnpCredDone ? 'text-emerald-600 bg-emerald-500/15' : 'text-primary bg-primary/10'">
                            {{ mnpCredDone ? "已完成" : "步骤 A" }}
                        </span>
                        <div>
                            <div class="text-base font-[1000] text-slate-900">填写微信小程序凭证</div>
                            <p class="mt-1 text-[13px] text-slate-500 leading-normal max-w-[68ch]">
                                在「微信公众平台 → 开发管理 → 开发设置」可找到 AppID、AppSecret、原始 ID。
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-x-6 gap-y-1 max-w-[720px] mt-5">
                        <ElForm label-position="top">
                            <ElFormItem label="小程序名称" required>
                                <ElInput v-model="tenant.mnp.name" placeholder="对外展示的名称" />
                            </ElFormItem>
                        </ElForm>
                        <ElForm label-position="top">
                            <ElFormItem label="原始 ID（可选）">
                                <ElInput v-model="tenant.mnp.original_id" placeholder="gh_ 开头，可选" />
                            </ElFormItem>
                        </ElForm>
                        <ElForm label-position="top">
                            <ElFormItem label="AppID" required>
                                <ElInput v-model="tenant.mnp.app_id" placeholder="wx 开头" />
                            </ElFormItem>
                        </ElForm>
                        <ElForm label-position="top">
                            <ElFormItem label="AppSecret" required>
                                <ElInput
                                    v-model="tenant.mnp.app_secret"
                                    type="password"
                                    show-password
                                    :placeholder="
                                        tenant.mnp.has_app_secret ? '••••••••（已配置，留空则保持不变）' : '小程序密钥'
                                    " />
                                <span
                                    v-if="tenant.mnp.has_app_secret && !tenant.mnp.app_secret"
                                    class="text-xs text-emerald-600 mt-1 inline-block"
                                    >✓ AppSecret 已配置</span
                                >
                            </ElFormItem>
                        </ElForm>
                    </div>

                    <ElForm label-position="top" class="max-w-[720px] mt-1">
                        <ElFormItem label="代码上传私钥" required>
                            <p class="text-xs text-slate-500 -mt-1 mb-2 leading-snug">
                                用于把代码包推到微信。在公众平台「开发管理 → 开发设置 →
                                小程序代码上传」生成并下载，把文件内容粘贴到这里。
                            </p>
                            <ElInput
                                v-model="tenant.mnp.private_key"
                                type="textarea"
                                :rows="3"
                                :placeholder="
                                    tenant.mnp.has_private_key
                                        ? '已配置，留空则保持不变；如需更换请粘贴新私钥'
                                        : '粘贴私钥全文（含 BEGIN / END 行）'
                                " />
                            <span
                                v-if="tenant.mnp.has_private_key && !tenant.mnp.private_key"
                                class="text-xs text-emerald-600 mt-1 inline-block"
                                >✓ 私钥已配置</span
                            >
                        </ElFormItem>
                        <ElFormItem label="小程序码（可选）">
                            <p class="text-xs text-slate-500 -mt-1 mb-2 leading-snug">
                                用于站内展示「扫码进入」，可稍后上传。
                            </p>
                            <BrandUpload
                                variant="qr"
                                :preview="tenant.mnp.qr_code"
                                title="上传小程序码"
                                hint="正方形清晰图"
                                @success="onMnpQrCode" />
                        </ElFormItem>
                        <ElFormItem label="是否开启审核">
                            <div class="flex items-center gap-3">
                                <ElSwitch v-model="tenant.mnp.audit" :active-value="1" :inactive-value="0" />
                                <span class="text-xs text-slate-500">开启后走微信审核流程（一般保持关闭即可）</span>
                            </div>
                        </ElFormItem>
                        <ElButton type="primary" class="!rounded-xl" @click="onSaveMnp">保存小程序凭证</ElButton>
                    </ElForm>
                </div>

                <!-- 服务器 / 业务 / OSS 域名（只读，照抄到微信公众平台） -->
                <div class="panel space-y-7">
                    <div>
                        <div class="section-title !mb-2"><span class="bar"></span>服务器域名</div>
                        <p class="text-[13px] text-slate-500 leading-normal max-w-[72ch] mb-5">
                            登录微信公众平台 → 开发 → 开发设置 → 服务器域名。OEM 小程序须同时配置「站点域名」与「主站域名」（每项可添加多个）。
                        </p>
                        <div class="space-y-5 max-w-[720px]">
                            <div v-for="row in mnpServerDomainRows" :key="row.key">
                                <div class="text-sm font-bold text-slate-700 mb-2">{{ row.label }}</div>
                                <div class="space-y-2">
                                    <div
                                        v-for="item in row.items"
                                        :key="`${row.key}-${item.tag}`"
                                        class="flex gap-2 items-center">
                                        <span
                                            class="shrink-0 text-[11px] font-bold px-2 py-1 rounded-md w-[44px] text-center"
                                            :class="
                                                item.tag === '站点'
                                                    ? 'text-primary bg-primary/10'
                                                    : 'text-slate-600 bg-slate-100'
                                            ">
                                            {{ item.tag }}
                                        </span>
                                        <ElInput
                                            :model-value="item.value"
                                            disabled
                                            class="flex-1"
                                            :placeholder="item.empty" />
                                        <ElButton
                                            class="!rounded-xl shrink-0"
                                            :disabled="!item.value"
                                            @click="onCopyDomain(item.value)">
                                            复制
                                        </ElButton>
                                    </div>
                                </div>
                                <p class="text-xs text-slate-400 mt-1.5 leading-snug">{{ row.tip }}</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="section-title !mb-2"><span class="bar"></span>业务域名</div>
                        <p class="text-[13px] text-slate-500 leading-normal max-w-[72ch] mb-5">
                            不含协议。站点与主站都需加入微信公众平台 → 开发 → 开发设置 → 业务域名。
                        </p>
                        <div class="space-y-2 max-w-[720px]">
                            <div
                                v-for="item in mnpBusinessDomainItems"
                                :key="`business-${item.tag}`"
                                class="flex gap-2 items-center">
                                <span
                                    class="shrink-0 text-[11px] font-bold px-2 py-1 rounded-md w-[44px] text-center"
                                    :class="
                                        item.tag === '站点'
                                            ? 'text-primary bg-primary/10'
                                            : 'text-slate-600 bg-slate-100'
                                    ">
                                    {{ item.tag }}
                                </span>
                                <ElInput
                                    :model-value="item.value"
                                    disabled
                                    class="flex-1"
                                    :placeholder="item.empty" />
                                <ElButton
                                    class="!rounded-xl shrink-0"
                                    :disabled="!item.value"
                                    @click="onCopyDomain(item.value)">
                                    复制
                                </ElButton>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="section-title !mb-2"><span class="bar"></span>{{ mnpOssDomain.label }}</div>
                        <p class="text-[13px] text-slate-500 leading-normal max-w-[72ch] mb-5">
                            当前平台存储方式的空间域名；若与上方域名不同，请一并加入微信的 downloadFile / uploadFile
                            合法域名。
                        </p>
                        <div class="max-w-[720px]">
                            <div class="flex gap-2 items-center">
                                <ElInput
                                    :model-value="mnpOssDomain.value"
                                    disabled
                                    class="flex-1"
                                    :placeholder="mnpOssDomain.empty" />
                                <ElButton
                                    class="!rounded-xl shrink-0"
                                    :disabled="!mnpOssDomain.value"
                                    @click="onCopyDomain(mnpOssDomain.value)">
                                    复制
                                </ElButton>
                            </div>
                            <p class="text-xs text-slate-400 mt-1 leading-snug">{{ mnpOssDomain.tip }}</p>
                        </div>
                    </div>
                </div>

                <!-- 阶段 2 -->
                <div class="panel" :class="{ 'opacity-70': !mnpCredDone }">
                    <div class="flex gap-3 items-start">
                        <span
                            class="shrink-0 text-[11px] font-[1000] px-2 py-1 rounded-full mt-0.5"
                            :class="mnpUploaded ? 'text-emerald-600 bg-emerald-500/15' : 'text-primary bg-primary/10'">
                            {{ mnpUploaded ? "已上传" : "步骤 B" }}
                        </span>
                        <div>
                            <div class="text-base font-[1000] text-slate-900">上传代码包并提交微信</div>
                            <p class="mt-1 text-[13px] text-slate-500 leading-normal max-w-[68ch]">
                                {{
                                    mnpCredDone
                                        ? "上传 .zip 代码包，填写版本号后提交。微信审核通过即可正式使用。"
                                        : "请先完成上方步骤 A 并保存凭证，再上传代码包。"
                                }}
                            </p>
                        </div>
                    </div>

                    <div class="steps mt-5 mb-5">
                        <span class="step text-slate-900"><i class="!bg-primary/20">1</i>上传代码包</span>
                        <span class="step-line"></span>
                        <span class="step" :class="mnpUploaded ? 'text-slate-900' : ''">
                            <i :class="mnpUploaded ? '!bg-primary/20' : ''">2</i>填写版本
                        </span>
                        <span class="step-line"></span>
                        <span class="step"><i>3</i>提交微信审核</span>
                    </div>

                    <ElForm label-position="top" class="max-w-[560px]">
                        <ElFormItem label="小程序代码包（.zip）" required>
                            <BrandUpload
                                variant="file"
                                :action="mnpUploadAction"
                                title="点击上传代码包"
                                hint="仅支持 .zip"
                                :done="mnpUploaded"
                                done-text="代码包已上传"
                                @success="onMnpCodeUploaded" />
                        </ElFormItem>
                        <div class="grid grid-cols-2 gap-4">
                            <ElFormItem label="版本号" required>
                                <ElInput v-model="mnpForm.upload_version" placeholder="如 1.0.0" />
                            </ElFormItem>
                        </div>
                        <ElFormItem label="更新说明">
                            <ElInput
                                v-model="mnpForm.upload_desc"
                                type="textarea"
                                :rows="2"
                                placeholder="本次更新了什么" />
                        </ElFormItem>
                        <ElButton
                            type="primary"
                            class="!rounded-xl"
                            :disabled="!mnpCredDone || mnpSubmitting"
                            :loading="mnpSubmitting"
                            @click="onSubmitMnp">
                            提交到微信
                        </ElButton>
                    </ElForm>
                </div>
            </section>

            <!-- 充值卡密 -->
            <section v-show="brandTab === 'card'" class="space-y-5">
                <div
                    class="flex gap-3.5 items-start px-[18px] py-4 rounded-2xl border border-primary/15 bg-gradient-to-br from-primary/[0.06] to-primary/[0.02]">
                    <div
                        class="w-8 h-8 rounded-[10px] grid place-items-center text-[13px] font-[1000] text-primary bg-white border border-primary/20 shrink-0">
                        卡
                    </div>
                    <div class="min-w-0">
                        <div class="text-[15px] font-[1000] text-slate-900">给站点用户发卡密 / 兑换码</div>
                        <p class="mt-1 text-[13px] leading-relaxed text-slate-600 max-w-[72ch]">
                            可生成算力卡（预扣算力）或会员兑换码。可转移给成员持有，用户兑换后到账；删除未使用的算力卡会退回算力。
                        </p>
                    </div>
                </div>

                <div class="panel">
                    <div class="flex items-center justify-between mb-5 flex-wrap gap-2">
                        <div class="section-title !mb-0"><span class="bar"></span>卡密列表</div>
                        <ElButton type="primary" class="!rounded-xl" @click="showGenerateCard = true">
                            生成卡密
                        </ElButton>
                    </div>
                    <ElTable
                        :data="cardPager.lists"
                        v-loading="cardPager.loading"
                        class="rounded-xl overflow-hidden">
                        <ElTableColumn label="卡号" min-width="200">
                            <template #default="{ row }">
                                <div class="flex items-center gap-2 min-w-0">
                                    <ElTooltip
                                        :content="String(row.card_code || '')"
                                        :disabled="!row.card_code"
                                        placement="top"
                                        :show-after="300">
                                        <span
                                            class="inline-block max-w-full min-w-0 truncate font-bold text-primary tracking-wide text-[13px] font-mono tabular-nums align-bottom">
                                            {{ row.card_code }}
                                        </span>
                                    </ElTooltip>
                                    <button
                                        v-if="row.card_code"
                                        type="button"
                                        class="shrink-0 h-6 px-2 rounded-md text-[12px] font-semibold text-primary bg-primary/10 hover:bg-primary/15 transition-colors"
                                        title="复制卡号"
                                        @click="onCopyCard(row.card_code)">
                                        复制
                                    </button>
                                </div>
                            </template>
                        </ElTableColumn>
                        <ElTableColumn label="类型" min-width="120">
                            <template #default="{ row }">
                                <span
                                    class="inline-flex items-center h-6 px-2.5 rounded-full text-[12px] font-semibold"
                                    :class="
                                        Number(row.type) === 6
                                            ? 'text-amber-700 bg-amber-500/15'
                                            : 'text-primary bg-primary/10'
                                    ">
                                    {{ row.type_desc || (Number(row.type) === 6 ? "会员兑换码" : "算力卡") }}
                                </span>
                            </template>
                        </ElTableColumn>
                        <ElTableColumn label="内容" min-width="150" show-overflow-tooltip prop="content">
                            <template #default="{ row }">
                                {{ row.content || (Number(row.type) === 6 ? "—" : `${row.tokens ?? 0} 算力`) }}
                            </template>
                        </ElTableColumn>
                        <ElTableColumn label="拥有者" min-width="120" show-overflow-tooltip>
                            <template #default="{ row }">
                                {{ row.owner_nickname || "-" }}
                            </template>
                        </ElTableColumn>
                        <ElTableColumn label="使用状态" width="110">
                            <template #default="{ row }">
                                <span
                                    class="inline-flex items-center h-6 px-2.5 rounded-full text-[12px] font-semibold"
                                    :class="
                                        row.status === 1
                                            ? 'text-slate-500 bg-slate-100'
                                            : 'text-emerald-600 bg-emerald-500/15'
                                    ">
                                    {{ row.status_desc || (row.status === 1 ? "已使用" : "未使用") }}
                                </span>
                            </template>
                        </ElTableColumn>
                        <ElTableColumn label="使用者" min-width="120" show-overflow-tooltip>
                            <template #default="{ row }">
                                {{ row.used_by_nickname || "—" }}
                            </template>
                        </ElTableColumn>
                        <ElTableColumn label="过期时间" min-width="160">
                            <template #default="{ row }">
                                <span
                                    class="text-[12px] tabular-nums"
                                    :class="row.expired ? 'text-red-600' : 'text-slate-500'">
                                    {{ row.valid_end_time || "永久有效" }}
                                </span>
                                <span
                                    v-if="row.expired"
                                    class="inline-flex items-center h-5 px-1.5 ml-1 rounded-full text-[11px] font-semibold text-red-600 bg-red-600/10">
                                    已过期
                                </span>
                            </template>
                        </ElTableColumn>
                        <ElTableColumn label="创建时间" min-width="160">
                            <template #default="{ row }">
                                <span class="text-[12px] text-slate-500 tabular-nums">{{
                                    row.create_time || "—"
                                }}</span>
                            </template>
                        </ElTableColumn>
                        <ElTableColumn label="操作" width="140" fixed="right">
                            <template #default="{ row }">
                                <ElButton
                                    v-if="row.status !== 1 && Number(row.remaining_uses) > 0"
                                    link
                                    type="primary"
                                    @click="openTransferCard(row)">
                                    转移
                                </ElButton>
                                <ElButton
                                    v-if="row.status !== 1 && Number(row.remaining_uses) > 0"
                                    link
                                    type="danger"
                                    @click="onDeleteCard(row)">
                                    删除
                                </ElButton>
                                <span v-else-if="row.status === 1" class="text-slate-300 text-xs">—</span>
                            </template>
                        </ElTableColumn>
                        <template #empty>
                            <ElEmpty description="还没有卡密，点击右上角「生成卡密」开始制作" />
                        </template>
                    </ElTable>
                    <div class="flex justify-end mt-4">
                        <pagination v-model="cardPager" @change="getCardLists" />
                    </div>
                </div>
            </section>

            <!-- 站点用户 -->
            <section v-show="brandTab === 'users'" class="space-y-5">
                <div
                    class="flex gap-3.5 items-start px-[18px] py-4 rounded-2xl border border-primary/15 bg-gradient-to-br from-primary/[0.06] to-primary/[0.02]">
                    <div
                        class="w-8 h-8 rounded-[10px] grid place-items-center text-[13px] font-[1000] text-primary bg-white border border-primary/20 shrink-0">
                        户
                    </div>
                    <div class="min-w-0">
                        <div class="text-[15px] font-[1000] text-slate-900">管理从你站点进来的用户</div>
                        <p class="mt-1 text-[13px] leading-relaxed text-slate-600 max-w-[72ch]">
                            通过你的域名或小程序注册的用户会出现在这里。可调整他们的算力，或解除归属（算力仍留在用户账上）。
                        </p>
                    </div>
                </div>

                <div class="panel">
                    <div class="flex items-center justify-between mb-5">
                        <div class="section-title !mb-0">
                            <span class="bar"></span>站点用户
                            <span class="text-slate-400 font-normal ml-1">({{ siteUsers.length }})</span>
                        </div>
                    </div>
                    <ElTable :data="siteUsers" class="rounded-xl overflow-hidden">
                        <ElTableColumn label="用户名称" min-width="180">
                            <template #default="{ row }">
                                <div class="flex items-center gap-2">
                                    <ElAvatar :size="32" :src="row.avatar" />
                                    <div class="min-w-0">
                                        <div class="font-medium truncate">{{ row.nickname }}</div>
                                        <div class="text-slate-400 text-xs">{{ row.sn }}</div>
                                    </div>
                                </div>
                            </template>
                        </ElTableColumn>
                        <ElTableColumn label="注册来源" min-width="110">
                            <template #default="{ row }">
                                <ElTag effect="light" round size="small">{{ row.channel_desc || "-" }}</ElTag>
                            </template>
                        </ElTableColumn>
                        <ElTableColumn label="用户手机号" prop="mobile" min-width="130" />
                        <ElTableColumn label="当前算力" min-width="110" align="right">
                            <template #default="{ row }">
                                <span class="font-bold text-primary">{{ formatNum(row.tokens) }}</span>
                            </template>
                        </ElTableColumn>
                        <ElTableColumn label="注册时间" prop="create_time" min-width="160" />
                        <ElTableColumn label="操作" width="180" fixed="right">
                            <template #default="{ row }">
                                <ElButton link type="primary" @click="onAdjustSiteUser(row)">调整算力</ElButton>
                                <ElButton link type="danger" @click="onRemoveSiteUser(row)">移除用户</ElButton>
                            </template>
                        </ElTableColumn>
                        <template #empty>
                            <ElEmpty description="暂无站点用户。配置好域名/小程序后，新注册用户会出现在这里" />
                        </template>
                    </ElTable>
                </div>
            </section>
        </template>
    </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import BrandUpload from "./brand-upload.vue";
import { useTeamContext } from "../_composables/context";
import { BRAND_TABS } from "../_enums";
import { useCopy } from "@/composables/useCopy";
import { formatNum } from "../_composables/helpers";

const { copy } = useCopy();
const onCopyCard = (code: string) => {
    if (!code) return;
    copy(String(code));
};
const onCopyDomain = (value: string) => {
    if (!value) return;
    copy(String(value));
};

const lockBenefits = [
    "独立域名与站点 LOGO",
    "自有微信小程序上传发布",
    "充值卡密发给你的用户",
    "站点注册用户归属与算力管理",
];

const { info: infoCtx, brand } = useTeamContext();
const { info, oemActive, oemStatus } = infoCtx;
const {
    brandTab,
    tenant,
    onBrandIcon,
    onBrandLogo,
    onBrandQr,
    onMnpQrCode,
    onSaveBrand,
    onSaveMnp,
    mnpUploadAction,
    mnpUploaded,
    mnpForm,
    onMnpCodeUploaded,
    mnpSubmitting,
    onSubmitMnp,
    showGenerateCard,
    cardPager,
    getCardLists,
    openTransferCard,
    onDeleteCard,
    siteUsers,
    onAdjustSiteUser,
    onRemoveSiteUser,
    showUpgrade,
} = brand;

/** 站点外观是否基本就绪 */
const siteDone = computed(
    () => !!(tenant.domain && tenant.brand?.name && tenant.brand?.web_logo && tenant.brand?.pc_logo),
);

/** 小程序凭证是否齐(密钥/私钥已配置也算完成;原始 ID 可选不参与判断) */
const mnpCredDone = computed(
    () =>
        !!(
            tenant.mnp?.name &&
            tenant.mnp?.app_id &&
            (tenant.mnp?.app_secret || tenant.mnp?.has_app_secret) &&
            (tenant.mnp?.private_key || tenant.mnp?.has_private_key)
        ),
);

const emptySiteDomain = "请先在「站点外观」填写站点域名";
const emptyMainDomain = "主站域名未配置";

type DomainItem = { tag: "站点" | "主站"; value: string; empty: string };

const buildDomainPair = (field: string): DomainItem[] => {
    const d = tenant.mnp_domains || {};
    const siteVal = String(d.site?.[field] || "");
    const mainVal = String(d.main?.[field] || "");
    return [
        { tag: "站点", value: siteVal, empty: emptySiteDomain },
        { tag: "主站", value: mainVal, empty: emptyMainDomain },
    ];
};

/** 服务器域名：站点 + 主站各一组协议域名 */
const mnpServerDomainRows = computed(() => [
    {
        key: "request_domain",
        label: "request合法域名",
        items: buildDomainPair("request_domain"),
        tip: "开发 → 开发设置 → 服务器域名，填写 https 协议域名（站点、主站都要加）",
    },
    {
        key: "socket_domain",
        label: "socket合法域名",
        items: buildDomainPair("socket_domain"),
        tip: "开发 → 开发设置 → 服务器域名，填写 wss 协议域名（站点、主站都要加）",
    },
    {
        key: "upload_file_domain",
        label: "uploadFile合法域名",
        items: buildDomainPair("upload_file_domain"),
        tip: "开发 → 开发设置 → 服务器域名，填写 https 协议域名（站点、主站都要加）",
    },
    {
        key: "download_file_domain",
        label: "downloadFile合法域名",
        items: buildDomainPair("download_file_domain"),
        tip: "开发 → 开发设置 → 服务器域名，填写 https 协议域名（站点、主站都要加）",
    },
    {
        key: "udp_domain",
        label: "udp合法域名",
        items: buildDomainPair("udp_domain"),
        tip: "开发 → 开发设置 → 服务器域名，填写 udp 协议域名（站点、主站都要加）",
    },
]);

/** 业务域名：站点 + 主站（不含协议） */
const mnpBusinessDomainItems = computed(() => buildDomainPair("business_domain"));

/** 当前存储引擎空间域名（七牛 / 阿里云 / 腾讯云 / 本地） */
const mnpOssDomain = computed(() => {
    const d = tenant.mnp_domains || {};
    const ossName = String(d.oss_engine_name || "OSS");
    return {
        label: `${ossName}域名`,
        value: String(d.oss_domain || ""),
        empty: "当前存储未配置空间域名",
        tip: `当前存储方式为「${ossName}」；与上方域名不同时，须额外加入 downloadFile / uploadFile 合法域名`,
    };
});

const setupSteps = computed(() => [
    {
        key: "site",
        n: 1,
        title: "站点外观",
        done: siteDone.value,
        doneText: "已配置域名与品牌",
        todoText: "域名 · 标题 · LOGO",
    },
    {
        key: "mnp",
        n: 2,
        title: "微信小程序",
        done: mnpCredDone.value,
        doneText: "凭证已保存",
        todoText: "AppID · 密钥 · 私钥",
    },
]);

const saveBrandThen = async () => {
    await onSaveBrand();
};
</script>

<style lang="scss" scoped>
@import "@/pages/team/_styles/console.scss";
</style>
