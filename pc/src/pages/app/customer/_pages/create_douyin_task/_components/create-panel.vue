<template>
    <div class="h-full bg-white rounded-2xl flex flex-col min-w-[1000px] border border-br overflow-hidden">
        <div
            class="flex-shrink-0 flex items-center justify-between px-8 h-[80px] border-b border-br-extra-light bg-white z-10">
            <div class="flex items-center gap-2 cursor-pointer group" @click="emit('back')">
                <div
                    class="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center group-hover:bg-blue-50 transition-colors">
                    <span class="text-tx-regular group-hover:text-primary leading-[0]">
                        <Icon name="el-icon-ArrowLeft"></Icon>
                    </span>
                </div>
                <div class="text-sm font-medium text-tx-regular">返回列表</div>
            </div>
            <div class="flex items-center gap-3">
                <ElButton
                    type="primary"
                    class="!rounded-full !h-11 px-8 !font-black shadow-light hover:scale-105 active:scale-95 transition-all"
                    :loading="isLock"
                    @click="lockFn">
                    确认发布任务
                </ElButton>
            </div>
        </div>

        <div class="grow min-h-0 bg-gray-50/50">
            <ElScrollbar>
                <div class="w-[640px] mx-auto py-10 pb-24">
                    <div class="mb-8">
                        <h1 class="text-3xl font-[900] text-gray-950">创建抖音获客任务</h1>
                        <p class="text-sm text-tx-secondary mt-2">搜寻抖音同行账号，自动识别联系方式并加微</p>
                    </div>

                    <div class="flex flex-col gap-y-3">
                        <div class="bg-white p-6 rounded-2xl border border-br-extra-light">
                            <div class="text-base font-black mb-6 flex items-center gap-2">
                                <span class="w-1 h-4 bg-primary rounded-full"></span>
                                基本信息
                            </div>

                            <div class="space-y-3">
                                <div>
                                    <ElFormItem label="任务名称" tip="给任务起一个好记的名字" />
                                    <ElInput
                                        v-model="formData.name"
                                        placeholder="例如：美妆行业抖音客户搜寻"
                                        class="custom-input"
                                        maxlength="30"
                                        show-word-limit
                                        clearable />
                                </div>

                                <div>
                                    <ElFormItem label="搜寻方式" />
                                    <div class="bg-slate-50 rounded-2xl p-1.5 flex w-full">
                                        <div
                                            v-for="opt in searchModeOptions"
                                            :key="opt.value"
                                            @click="formData.search_mode = opt.value"
                                            :class="[
                                                'flex-1 py-3 text-center text-sm font-black rounded-xl cursor-pointer transition-all duration-300',
                                                formData.search_mode === opt.value
                                                    ? 'bg-white text-primary shadow-sm'
                                                    : 'text-slate-400 hover:text-slate-600',
                                            ]">
                                            {{ opt.label }}
                                        </div>
                                    </div>
                                    <p class="text-xs text-tx-placeholder mt-2">
                                        {{
                                            formData.search_mode === SearchModeEnum.KEYWORD
                                                ? "用行业泛词搜一批同类账号"
                                                : "已有抖音号名单，逐个进去查"
                                        }}
                                    </p>
                                </div>

                                <div>
                                    <ElFormItem label="执行设备" />
                                    <ElSelect
                                        v-model="formData.device_codes"
                                        multiple
                                        collapse-tags
                                        class="custom-select w-full">
                                        <ElOption
                                            v-for="item in deviceOptions.deviceLists"
                                            :key="item.device_code"
                                            :label="item.device_name"
                                            :value="item.device_code" />
                                    </ElSelect>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-2xl border border-br-extra-light">
                            <div class="flex justify-between items-center mb-6">
                                <div class="text-base font-black flex items-center gap-2">
                                    <span class="w-1 h-4 bg-primary rounded-full"></span>
                                    {{ formData.search_mode === SearchModeEnum.KEYWORD ? "检索关键词" : "指定账号" }}
                                </div>
                                <div class="flex gap-2">
                                    <ElButton
                                        v-if="formData.search_mode === SearchModeEnum.KEYWORD"
                                        type="primary"
                                        link
                                        class="!text-xs font-medium"
                                        @click="handleAddClue('ai')">
                                        <Icon name="el-icon-MagicStick" />
                                        <span class="ml-1">AI 智能扩词</span>
                                    </ElButton>
                                    <ElButton
                                        type="info"
                                        link
                                        class="!text-xs font-medium"
                                        @click="handleAddClue('manual')">
                                        <Icon name="el-icon-Plus" />
                                        <span class="ml-1">手动添加</span>
                                    </ElButton>
                                </div>
                            </div>

                            <div class="space-y-3 max-h-[320px] overflow-y-auto pr-2 custom-scrollbar">
                                <div
                                    v-for="(item, index) in currentClueList"
                                    :key="index"
                                    class="flex items-center gap-3 bg-gray-50 p-2 rounded-xl border border-[transparent] hover:border-br-light hover:bg-white transition-all group">
                                    <div
                                        class="w-8 h-8 flex-shrink-0 rounded-lg bg-white shadow-sm flex items-center justify-center text-xs font-medium text-tx-secondary group-hover:text-primary">
                                        {{ index + 1 }}
                                    </div>
                                    <ElInput
                                        v-model="currentClueList[index]"
                                        :placeholder="
                                            formData.search_mode === SearchModeEnum.KEYWORD
                                                ? '请输入关键词'
                                                : '请输入抖音号'
                                        "
                                        class="flex-1 keyword-input" />
                                    <div
                                        @click="handleClueDelete(index)"
                                        class="p-2 cursor-pointer opacity-0 group-hover:opacity-100 text-tx-placeholder hover:text-danger transition-all">
                                        <Icon name="el-icon-Delete" :size="16" />
                                    </div>
                                </div>
                            </div>

                            <template v-if="formData.search_mode === SearchModeEnum.KEYWORD">
                                <div class="grid grid-cols-2 gap-4 mt-3">
                                    <div>
                                        <ElFormItem label="每个关键词最多取数" />
                                        <ElSelect v-model="formData.per_kw" class="custom-select w-full">
                                            <ElOption
                                                v-for="item in PER_KEYWORD_OPTIONS"
                                                :key="item.value"
                                                :label="item.label"
                                                :value="item.value" />
                                        </ElSelect>
                                    </div>
                                    <div class="flex items-end">
                                        <div class="w-full bg-slate-50 border border-br-extra-light rounded-xl px-4 py-3 flex items-center justify-between">
                                            <span class="text-xs text-tx-regular">账号初筛</span>
                                            <ElSwitch v-model="formData.pre_filter" />
                                        </div>
                                    </div>
                                </div>

                                <div v-if="formData.pre_filter" class="mt-3 bg-gray-50 rounded-xl p-4">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <ElFormItem label="最少粉丝数" />
                                            <ElInputNumber
                                                v-model="formData.fans_min"
                                                :min="0"
                                                controls-position="right"
                                                class="!w-full" />
                                        </div>
                                        <div>
                                            <ElFormItem label="最少作品数" />
                                            <ElInputNumber
                                                v-model="formData.works_min"
                                                :min="0"
                                                controls-position="right"
                                                class="!w-full" />
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="bg-white p-6 rounded-2xl border border-br-extra-light">
                            <div class="text-base font-black mb-6 flex items-center gap-2">
                                <span class="w-1 h-4 bg-primary rounded-full"></span>
                                扫描与识别
                            </div>

                            <div class="space-y-5">
                                <div>
                                    <div class="text-sm font-medium text-tx-regular mb-3">查看哪些位置</div>
                                    <ElCheckboxGroup v-model="formData.scan_positions">
                                        <ElCheckbox
                                            v-for="item in SCAN_POSITION_OPTIONS"
                                            :key="item.value"
                                            :value="item.value">
                                            {{ item.label }}
                                            <span class="text-xs text-tx-placeholder ml-1">{{ item.desc }}</span>
                                        </ElCheckbox>
                                    </ElCheckboxGroup>
                                </div>

                                <div>
                                    <div class="text-sm font-medium text-tx-regular mb-3">识别哪些联系方式</div>
                                    <ElCheckboxGroup v-model="formData.contact_types">
                                        <ElCheckbox
                                            v-for="item in CONTACT_TYPE_OPTIONS"
                                            :key="item.value"
                                            :value="item.value">
                                            {{ item.label }}
                                        </ElCheckbox>
                                    </ElCheckboxGroup>
                                    <div v-if="hasPhoneContact" class="mt-2">
                                        <ElCheckbox v-model="formData.phone_as_wechat">
                                            手机号同时当微信号去加
                                            <span class="text-xs text-tx-placeholder">这类账号大多手机号就是微信号</span>
                                        </ElCheckbox>
                                    </div>
                                </div>

                                <div class="bg-blue-50 border border-blue-100 rounded-xl p-3 text-xs text-blue-500">
                                    <div class="flex items-center gap-1 text-blue-600 mb-1">
                                        <Icon name="el-icon-InfoFilled" :size="14" />
                                        <span class="font-medium">内置规则</span>
                                    </div>
                                    系统分配的默认抖音号不作为联系方式采集，该规则固定开启。
                                </div>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-2xl border border-br-extra-light">
                            <div class="text-base font-black mb-6 flex items-center gap-2">
                                <span class="w-1 h-4 bg-orange-500 rounded-full"></span>
                                没识别到联系方式时
                            </div>

                            <div class="space-y-4">
                                <div class="bg-slate-50 rounded-2xl p-1.5 flex w-full">
                                    <div
                                        v-for="opt in MISS_ACTION_OPTIONS"
                                        :key="opt.value"
                                        @click="formData.miss_action = opt.value"
                                        :class="[
                                            'flex-1 py-2.5 text-center text-sm font-black rounded-xl cursor-pointer transition-all duration-300',
                                            formData.miss_action === opt.value
                                                ? 'bg-white text-primary shadow-sm'
                                                : 'text-slate-400 hover:text-slate-600',
                                        ]">
                                        {{ opt.label }}
                                    </div>
                                </div>

                                <template v-if="formData.miss_action === MissActionEnum.DM">
                                    <div
                                        class="p-5 bg-slate-50 rounded-[20px] border border-dashed border-slate-200">
                                        <div class="text-xs text-tx-placeholder mb-3">私信话术（随机取一条发）</div>
                                        <div class="flex flex-wrap gap-2.5">
                                            <div
                                                v-for="(item, index) in formData.miss_scripts"
                                                :key="index"
                                                class="bg-white border border-slate-100 pl-3 pr-2 py-2 rounded-xl flex items-center shadow-sm group hover:border-primary transition-all">
                                                <input
                                                    v-model="formData.miss_scripts[index]"
                                                    :ref="(el) => (missScriptRefs[index] = el)"
                                                    :size="Math.max(item.length + 2, 6)"
                                                    placeholder="请输入话术"
                                                    class="bg-transparent outline-none text-xs font-medium text-slate-600" />
                                                <button
                                                    @click="formData.miss_scripts.splice(index, 1)"
                                                    class="w-5 h-5 rounded-md bg-slate-100 text-slate-400 hover:bg-red-500 hover:text-white transition-colors flex items-center justify-center">
                                                    <Icon name="el-icon-Close" :size="10" />
                                                </button>
                                            </div>
                                            <button
                                                @click="handleAddMissScript"
                                                class="h-[34px] px-4 rounded-xl border-2 border-dashed border-[#0065fb]/30 text-primary hover:bg-primary hover:text-white transition-all flex items-center gap-2 text-xs font-black">
                                                <Icon name="el-icon-Plus" /> 添加话术
                                            </button>
                                        </div>
                                    </div>
                                    <div>
                                        <ElCheckbox v-model="formData.miss_once">
                                            同一个账号只问一次
                                            <span class="text-xs text-tx-placeholder">问过没回的不再打扰</span>
                                        </ElCheckbox>
                                    </div>
                                    <div class="p-3 rounded-xl bg-[#FFF8E9] border border-[#FFE7BA] text-xs text-[#AD6800]">
                                        私信要占用账号的私信额度，也更容易触发风控，建议配合下方单日上限一起使用。
                                    </div>
                                </template>
                                <div
                                    v-else
                                    class="p-3 rounded-xl bg-[#F2F7FF] border border-[#D6E6FF] text-xs text-[#2A5FAE]">
                                    不打扰账号，直接记入已看过列表，下次搜到同一个号自动跳过。
                                </div>
                            </div>
                        </div>

                        <!-- 自动加好友设置 -->
                        <div
                            v-if="friendAddEnabled"
                            class="bg-white p-6 rounded-2xl border border-br-extra-light">
                            <div class="flex justify-between items-center mb-6">
                                <div class="text-base font-black flex items-center gap-2">
                                    <span class="w-1 h-4 bg-orange-500 rounded-full"></span>
                                    自动加好友设置
                                </div>
                                <ElSwitch
                                    v-model="formData.add_type"
                                    active-value="1"
                                    inactive-value="0" />
                            </div>

                            <div v-if="formData.add_type === '1'" class="space-y-5">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <ElFormItem label="加微微信" tip="选择执行加好友的微信号">
                                            <ElSelect
                                                v-model="formData.wechat_id"
                                                multiple
                                                collapse-tags
                                                class="custom-select w-full"
                                                placeholder="选择微信号">
                                                <ElOption
                                                    v-for="item in deviceOptions.wechatLists"
                                                    :key="item.account"
                                                    :label="item.nickname"
                                                    :value="item.account" />
                                            </ElSelect>
                                        </ElFormItem>
                                    </div>
                                    <div>
                                        <ElFormItem label="加微规则">
                                            <ElSelect v-model="formData.add_rule" class="custom-select w-full">
                                                <ElOption
                                                    v-for="opt in ADD_RULE_OPTIONS"
                                                    :key="opt.value"
                                                    :label="opt.label"
                                                    :value="opt.value" />
                                            </ElSelect>
                                        </ElFormItem>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <ElFormItem label="每次新增数量" tip="每次连续加多少人">
                                            <ElInputNumber
                                                v-model="formData.add_number"
                                                :min="1"
                                                :max="50"
                                                controls-position="right"
                                                class="!w-full" />
                                        </ElFormItem>
                                    </div>
                                    <div>
                                        <ElFormItem label="每次间隔（分钟）">
                                            <ElInputNumber
                                                v-model="formData.add_interval_time"
                                                :min="1"
                                                :max="60"
                                                controls-position="right"
                                                class="!w-full" />
                                        </ElFormItem>
                                    </div>
                                </div>

                                <div class="bg-blue-50 border border-blue-100 rounded-xl p-3 text-xs text-blue-500">
                                    <div class="flex items-center gap-1 text-blue-600 mb-1">
                                        <Icon name="el-icon-InfoFilled" :size="14" />
                                        <span class="font-medium">风控提示</span>
                                    </div>
                                    间隔太短、数量过多都可能触发微信风控，建议每 5-10 分钟加 3-5 人。
                                </div>

                                <!-- 加好友备注内容 -->
                                <div class="bg-slate-50 rounded-xl p-4">
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="text-sm font-medium text-tx-regular">加好友备注内容</span>
                                        <ElSwitch
                                            v-model="formData.add_remark_enable"
                                            :active-value="1"
                                            :inactive-value="0" />
                                    </div>
                                    <template v-if="formData.add_remark_enable === 1">
                                        <div class="flex flex-wrap gap-2.5">
                                            <div
                                                v-for="(item, index) in formData.remarks"
                                                :key="index"
                                                class="bg-white border border-slate-100 pl-3 pr-2 py-2 rounded-xl flex items-center shadow-sm group hover:border-primary transition-all">
                                                <input
                                                    v-model="formData.remarks[index]"
                                                    type="text"
                                                    placeholder="请输入备注"
                                                    class="bg-transparent outline-none text-xs font-medium text-slate-600 w-40" />
                                                <button
                                                    @click="formData.remarks.splice(index, 1)"
                                                    class="w-5 h-5 rounded-md bg-slate-100 text-slate-400 hover:bg-red-500 hover:text-white transition-colors flex items-center justify-center ml-2">
                                                    <Icon name="el-icon-Close" :size="10" />
                                                </button>
                                            </div>
                                            <button
                                                @click="formData.remarks.push('')"
                                                class="h-[34px] px-4 rounded-xl border-2 border-dashed border-[#0065fb]/30 text-primary hover:bg-primary hover:text-white transition-all flex items-center gap-2 text-xs font-black">
                                                <Icon name="el-icon-Plus" /> 添加备注
                                            </button>
                                        </div>
                                        <p class="text-xs text-tx-placeholder mt-3">
                                            随机取一条发，带上来源（「抖音看到的」）通过率明显更高。
                                        </p>
                                    </template>
                                    <template v-else>
                                        <p class="text-xs text-tx-placeholder leading-relaxed">
                                            已关闭。发好友申请时不带验证语，通过率会低一些。
                                        </p>
                                    </template>
                                </div>
                            </div>

                            <div
                                v-else
                                class="p-3 rounded-xl bg-[#F2F7FF] border border-[#D6E6FF] text-xs text-[#2A5FAE]">
                                已关闭。识别到的联系方式只存进客资库，不会自动去加。
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-2xl border border-br-extra-light">
                            <div class="text-base font-black mb-6 flex items-center gap-2">
                                <span class="w-1 h-4 bg-primary rounded-full"></span>
                                执行方式与频率
                            </div>

                            <div class="space-y-6">
                                <div>
                                    <div class="text-sm font-medium text-tx-regular mb-4">执行类型</div>
                                    <div class="bg-slate-50 rounded-2xl p-1.5 inline-flex w-full">
                                        <div
                                            v-for="opt in taskExecTypeOptions"
                                            :key="opt.value"
                                            @click="formData.task_exec_type = opt.value"
                                            :class="[
                                                'flex-1 py-3 text-center text-sm font-black rounded-xl cursor-pointer transition-all duration-300 flex items-center justify-center gap-2',
                                                formData.task_exec_type === opt.value
                                                    ? 'bg-white text-primary shadow-sm'
                                                    : 'text-slate-400 hover:text-slate-600',
                                            ]">
                                            <Icon :name="opt.icon" :size="16" />
                                            <span>{{ opt.label }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="formData.task_exec_type === 1" class="space-y-4">
                                    <div
                                        class="p-3 rounded-2xl border border-slate-100 bg-gradient-to-r from-[#eff6ff]/50 to-[#e0e7ff]/50">
                                        <div class="flex justify-between items-center mb-4">
                                            <div>
                                                <div class="text-sm font-black text-slate-700 mb-1">任务执行时长</div>
                                                <div class="text-xs text-slate-400">
                                                    当内容执行完成后，任务会根据设定时间提前结束
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <button
                                                    @click="handleExecuteMinuteChange(-1)"
                                                    class="w-10 h-10 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 flex items-center justify-center transition-colors">
                                                    <Icon name="el-icon-Minus" :size="16" />
                                                </button>
                                                <div class="flex items-center gap-1 min-w-[80px] justify-center">
                                                    <ElInput
                                                        v-model="formData.minutes"
                                                        v-number-input="{ min: 1, max: 9999 }"
                                                        class="!w-16 text-center font-bold" />
                                                    <span class="text-sm text-slate-500">分钟</span>
                                                </div>
                                                <button
                                                    @click="handleExecuteMinuteChange(1)"
                                                    class="w-10 h-10 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 flex items-center justify-center transition-colors">
                                                    <Icon name="el-icon-Plus" :size="16" />
                                                </button>
                                            </div>
                                        </div>
                                        <div class="bg-blue-50 rounded-xl p-3 border border-blue-100">
                                            <div class="flex items-center gap-2 text-blue-600">
                                                <Icon name="el-icon-InfoFilled" :size="14" />
                                                <span class="text-xs font-medium">立即执行模式</span>
                                            </div>
                                            <div class="text-xs text-blue-500 mt-1">
                                                任务将在确认后立即开始执行，请确保设备在线
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="formData.task_exec_type === 0">
                                    <div class="text-sm font-medium text-tx-regular mb-4">执行周期</div>
                                    <div class="flex flex-wrap gap-2">
                                        <div
                                            v-for="item in [1, 3, 5, 10, 30]"
                                            :key="item"
                                            class="px-5 py-2 rounded-xl cursor-pointer text-sm font-medium transition-all border"
                                            :class="
                                                formData.task_frep == item && currentFrequency != 5
                                                    ? 'bg-primary text-white border-primary shadow-light'
                                                    : 'bg-white text-tx-secondary border-br-light hover:border-primary'
                                            "
                                            @click="handleFrequency(item, 0)">
                                            {{ item }}天
                                        </div>
                                        <div
                                            class="px-5 py-2 rounded-xl cursor-pointer text-sm font-medium transition-all border"
                                            :class="
                                                currentFrequency == 5
                                                    ? 'bg-primary text-white border-primary shadow-light'
                                                    : 'bg-white text-tx-secondary border-br-light hover:border-primary'
                                            "
                                            @click="currentFrequency = 5">
                                            自定义日期
                                        </div>
                                    </div>
                                    <div v-if="currentFrequency == 5" class="mt-4">
                                        <ElDatePicker
                                            v-model="formData.custom_date"
                                            class="!w-full custom-date-picker"
                                            placeholder="请选择日期"
                                            type="dates"
                                            value-format="YYYY-MM-DD"
                                            :disabled-date="getDisabledTaskDate" />
                                    </div>
                                </div>

                                <div class="pt-4 border-t border-dashed border-br-light">
                                    <div class="text-sm font-medium text-tx-regular mb-4">每日执行时段</div>
                                    <template v-if="formData.task_exec_type === 1">
                                        <div
                                            class="flex items-center justify-between h-16 px-4 bg-green-50 border border-green-200 rounded-xl">
                                            <span class="text-sm font-medium text-green-700">今日发布时间</span>
                                            <div
                                                class="px-4 py-2 rounded-full bg-green-100 text-green-600 font-medium text-sm">
                                                立即执行
                                            </div>
                                        </div>
                                    </template>
                                    <template v-else>
                                        <ElTimePicker
                                            v-model="formData.time_config"
                                            type="time"
                                            is-range
                                            range-separator="至"
                                            start-placeholder="开始"
                                            end-placeholder="结束"
                                            format="HH:mm"
                                            value-format="HH:mm"
                                            class="!w-full custom-time-picker"
                                            :show-arrow="false" />
                                    </template>
                                </div>

                                <div>
                                    <ElFormItem label="每日获客上限" />
                                    <ElSelect v-model="formData.day_cap" class="custom-select w-full">
                                        <ElOption
                                            v-for="item in DAY_CAP_OPTIONS"
                                            :key="item.value"
                                            :label="item.label"
                                            :value="item.value" />
                                    </ElSelect>
                                </div>
                            </div>
                        </div>

                        <!-- 任务冲突提示 -->
                        <div v-if="taskErrorMsg" class="bg-red-50 border border-red-200 rounded-2xl p-6">
                            <div class="flex items-center gap-2 text-red-600 mb-2">
                                <Icon name="el-icon-WarningFilled" :size="16" />
                                <span class="font-medium">任务冲突</span>
                            </div>
                            <div class="text-red-700 text-sm">{{ taskErrorMsg }}</div>
                        </div>
                    </div>
                </div>
            </ElScrollbar>
        </div>
    </div>
    <ai-add-keyword
        v-if="isAddKeywordGen"
        ref="aiAddKeywordRef"
        :type="3"
        @close="isAddKeywordGen = false"
        @success="handleAddKeywordSuccess" />
    <task-conflict-pop
        v-if="showTaskConflictDialog"
        ref="taskConflictPopRef"
        :messages="taskConflictMessages.messages"
        :errors="taskConflictMessages.errors"
        @close="showTaskConflictDialog = false"
        @confirm="handleConfirmCreateTask" />
</template>

<script setup lang="ts">
import { createDouyinTask, getDouyinTaskHistory } from "@/api/customer";
import { checkTaskPublishTime } from "@/api/device";
import dayjs from "dayjs";
import { useCreateTask } from "@/pages/app/customer/_hooks/useCreateTask";
import AiAddKeyword from "../../create_task/_components/ai-add-keyword.vue";
import TaskConflictPop from "@/pages/app/_components/task-conflict-pop.vue";
import { useAppStore } from "@/stores/app";
import {
    ADD_RULE_OPTIONS,
    CONTACT_TYPE_OPTIONS,
    ContactTypeEnum,
    DAY_CAP_OPTIONS,
    MISS_ACTION_OPTIONS,
    MissActionEnum,
    PER_KEYWORD_OPTIONS,
    SCAN_POSITION_OPTIONS,
    SearchModeEnum,
} from "../_enums";
import { buildDouyinPayload, createDefaultDouyinForm, DouyinFormData } from "../_hooks/douyin-payload";

const emit = defineEmits(["back"]);

const appStore = useAppStore();

/** 后台「手动任务开关 - 抖音搜寻获客 - 自动加好友」关闭时隐藏加微设置 */
const friendAddEnabled = computed(() => appStore.getManualTaskSwitch.dy_search_friend_add);

const formData = reactive<DouyinFormData>(createDefaultDouyinForm());

// 配置可能在页面挂载后才返回，开关状态变化时同步
watch(friendAddEnabled, (enabled) => {
    formData.add_type = enabled ? "1" : "0";
});

const currentClueList = computed(() =>
    formData.search_mode === SearchModeEnum.KEYWORD ? formData.keywords : formData.target_accounts,
);
const hasPhoneContact = computed(() => formData.contact_types.includes(ContactTypeEnum.PHONE));
const isMissDm = computed(() => formData.miss_action === MissActionEnum.DM);

const searchModeOptions = [
    { label: "关键词搜寻", value: SearchModeEnum.KEYWORD },
    { label: "指定账号", value: SearchModeEnum.ACCOUNT },
];

const taskExecTypeOptions = [
    { icon: "el-icon-VideoPlay", label: "立即执行", value: 1 },
    { icon: "el-icon-Clock", label: "定时执行", value: 0 },
];

const getDisabledTaskDate = (time: Date) => time.getTime() < dayjs().startOf("day").valueOf();

const {
    deviceOptions,
    currentFrequency,
    handleFrequency,
    checkTimeConfig,
} = useCreateTask(formData);

const isAddKeywordGen = ref(false);
const aiAddKeywordRef = shallowRef<InstanceType<typeof AiAddKeyword>>();
const handleAddClue = async (type: "ai" | "manual") => {
    if (type == "ai") {
        isAddKeywordGen.value = true;
        await nextTick();
        aiAddKeywordRef.value.open();
    } else {
        currentClueList.value.push("");
    }
};

const handleAddKeywordSuccess = (keywords: string[]) => {
    // 先清掉空白行，再追加 AI 生成的词，避免空行夹在中间
    const list = currentClueList.value;
    for (let i = list.length - 1; i >= 0; i--) {
        if (!list[i].trim()) list.splice(i, 1);
    }
    list.push(...keywords);
};

const handleClueDelete = (index: number) => {
    if (currentClueList.value.length == 1) {
        feedback.msgWarning(
            formData.search_mode === SearchModeEnum.KEYWORD ? "检索关键词至少存在一个！" : "指定账号至少存在一个！",
        );
        return;
    }
    currentClueList.value.splice(index, 1);
};

const missScriptRefs = ref<HTMLInputElement[]>([]);
const handleAddMissScript = () => {
    formData.miss_scripts.push("");
    nextTick(() => {
        missScriptRefs.value[formData.miss_scripts.length - 1]?.focus();
    });
};

const handleExecuteMinuteChange = (delta: number) => {
    const newValue = formData.minutes + delta;
    if (newValue >= 1) {
        formData.minutes = newValue;
    }
};

// 进面板回填上次填过的线索词 / 私信话术 / 加微备注；历史为空保留内置默认话术
// 指定账号名单后端不记历史，同移动端只回填 keywords
const historyKeywordsSnapshot = ref<string[]>([]);
const queryHistory = async () => {
    try {
        const res: any = await getDouyinTaskHistory();
        if (!res) return;
        const applyHistory = (list: unknown, target: string[]) => {
            if (Array.isArray(list) && list.length) {
                target.splice(0, target.length, ...list.map((item) => String(item)));
            }
        };
        // 快照本次回填的历史关键词，提交时对比表单算出「被删掉的词」随单上报
        applyHistory(res.keywords, historyKeywordsSnapshot.value);
        applyHistory(res.keywords, formData.keywords);
        applyHistory(res.miss_scripts, formData.miss_scripts);
        applyHistory(res.remarks, formData.remarks);
    } catch (error) {
        // 历史拉取失败不该挡住建任务，静默降级到默认话术
    }
};

onMounted(queryHistory);

const taskErrorMsg = ref("");
const showTaskConflictDialog = ref(false);
const taskConflictMessages = reactive<{ messages: string[]; errors: any[] }>({ messages: [], errors: [] });
const taskConflictPopRef = shallowRef<InstanceType<typeof TaskConflictPop>>();

const executeCreateTask = async () => {
    try {
        // 本次会话回填过的历史关键词里被用户从表单删掉的，随单上报让后端从历史同步移除
        const currentKeywords = new Set(formData.keywords.map((item) => item.trim()));
        const removedKeywords =
            formData.search_mode === SearchModeEnum.KEYWORD
                ? historyKeywordsSnapshot.value.filter((item) => !currentKeywords.has(item))
                : [];
        await createDouyinTask(buildDouyinPayload(formData, removedKeywords));
        feedback.msgSuccess("创建成功");
        emit("back");
    } catch (error: any) {
        taskErrorMsg.value = error;
        feedback.msgError(error);
    }
};

const handleConfirmCreateTask = async () => {
    showTaskConflictDialog.value = false;
    await executeCreateTask();
};

const { lockFn, isLock } = useLockFn(async () => {
    if (!formData.name) {
        feedback.msgWarning("请输入任务名称");
        return;
    } else if (formData.device_codes.length == 0) {
        feedback.msgWarning("请选择执行设备");
        return;
    } else if (currentClueList.value.length == 0 || currentClueList.value.every((item) => !item.trim())) {
        feedback.msgWarning(
            formData.search_mode === SearchModeEnum.KEYWORD ? "请输入检索关键词" : "请输入指定账号",
        );
        return;
    } else if (formData.scan_positions.length == 0) {
        feedback.msgWarning("至少选择一个查看位置");
        return;
    } else if (formData.contact_types.length == 0) {
        feedback.msgWarning("至少选择一种联系方式");
        return;
    } else if (isMissDm.value && formData.miss_scripts.filter((item) => item.trim()).length == 0) {
        feedback.msgWarning("请填写私信话术，或将「没识别到」改成直接跳过");
        return;
    } else if (formData.add_type === "1" && formData.wechat_id.length == 0) {
        feedback.msgWarning("请选择加微微信");
        return;
    } else if (
        formData.add_type === "1" &&
        formData.add_remark_enable === 1 &&
        formData.remarks.filter((item) => item.trim()).length == 0
    ) {
        feedback.msgWarning("请填写加好友备注内容");
        return;
    } else if (formData.task_exec_type === 0) {
        if (currentFrequency.value == 5 && formData.custom_date.length == 0) {
            feedback.msgWarning("请选择自定义日期");
            return;
        } else if (!checkTimeConfig()) {
            return;
        }
    }

    // 立即执行需要检测冲突
    if (formData.task_exec_type === 1) {
        try {
            const {
                messages,
                task_ids,
                errors: rawErrors,
            } = await checkTaskPublishTime({
                device_codes: formData.device_codes,
                minutes: formData.minutes,
            });

            if ((messages && messages.length > 0) || (rawErrors && rawErrors.length > 0)) {
                taskConflictMessages.messages = messages;
                taskConflictMessages.errors = rawErrors;
                formData.task_ids = task_ids;
                showTaskConflictDialog.value = true;
                await nextTick();
                taskConflictPopRef.value?.open();
                return;
            }
        } catch (error: any) {
            taskErrorMsg.value = error;
            feedback.msgError(error);
            return;
        }
    }

    await executeCreateTask();
});
</script>

<style scoped lang="scss">
:deep(.el-form-item) {
    @apply mb-1;
}

:deep(.custom-input),
:deep(.custom-select),
:deep(.custom-date-picker) {
    .el-input__wrapper {
        @apply rounded-xl bg-slate-50 border-[none] shadow-[none] h-12 px-4 transition-all;
        &:hover {
            @apply bg-slate-100;
        }
        &.is-focus {
            @apply bg-white shadow-[0_0_0_2px_#0065fb];
        }
    }
}

:deep(.custom-time-picker) {
    @apply bg-slate-50;
}
</style>
