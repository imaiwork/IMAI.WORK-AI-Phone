<template>
    <view class="min-h-screen bg-[#f8f9fa] pb-[160rpx] relative">
        <u-navbar
            :border-bottom="false"
            :background="{ background: '#ffffff' }"
            title="24h自动任务流"
            title-bold
            :custom-back="back">
        </u-navbar>
        <template v-if="isLoading">
            <view class="bg-white px-[30rpx] pt-[16rpx] pb-[32rpx] rounded-b-[32rpx] shadow-sm">
                <view class="flex items-center justify-end gap-[12rpx] mb-[16rpx]">
                    <view class="h-[48rpx] w-[100rpx] bg-[#F3F4F6] rounded-full animate-pulse"></view>
                </view>
                <view class="rounded-[24rpx] px-[28rpx] py-[24rpx] bg-[#F3F4F6] animate-pulse">
                    <view class="flex items-start justify-between">
                        <view class="flex-1">
                            <view class="flex items-center gap-[12rpx] mb-[16rpx]">
                                <view class="h-[32rpx] w-[100rpx] bg-[#E5E7EB] rounded-full"></view>
                                <view class="h-[32rpx] w-[80rpx] bg-[#E5E7EB] rounded-full"></view>
                            </view>
                            <view class="h-[36rpx] w-[240rpx] bg-[#E5E7EB] rounded-full"></view>
                        </view>
                        <view class="w-[56rpx] h-[56rpx] rounded-full bg-[#E5E7EB] flex-shrink-0 ml-[16rpx]"></view>
                    </view>
                </view>
            </view>
            <view class="px-[30rpx] mt-[32rpx]">
                <view v-for="(group, gIdx) in skeletonGroups" :key="gIdx" class="mb-[24rpx]">
                    <view class="flex items-center gap-[12rpx] mb-[20rpx]">
                        <view class="w-[16rpx] h-[16rpx] rounded-full bg-[#F3F4F6] animate-pulse"></view>
                        <view class="h-[28rpx] w-[120rpx] bg-[#F3F4F6] rounded-full animate-pulse"></view>
                        <view class="ml-auto h-[24rpx] w-[160rpx] bg-[#F3F4F6] rounded-full animate-pulse"></view>
                    </view>
                    <view
                        v-for="(cardWidth, cIdx) in group"
                        :key="cIdx"
                        class="bg-white rounded-[24rpx] p-[28rpx] mb-[20rpx] animate-pulse">
                        <view class="flex items-center gap-[16rpx] mb-[20rpx]">
                            <view class="w-[48rpx] h-[48rpx] rounded-full bg-[#F3F4F6] flex-shrink-0"></view>
                            <view class="flex-1">
                                <view
                                    class="h-[28rpx] bg-[#F3F4F6] rounded-full mb-[10rpx]"
                                    :style="`width:${cardWidth[0]}rpx`"></view>
                                <view class="h-[22rpx] w-[100rpx] bg-[#F3F4F6] rounded-full"></view>
                            </view>
                            <view class="h-[28rpx] w-[80rpx] bg-[#F3F4F6] rounded-full"></view>
                        </view>
                        <view class="h-[2rpx] bg-[#F3F4F6] rounded mb-[20rpx]"></view>
                        <view class="flex gap-[12rpx]">
                            <view
                                v-for="(tagWidth, tIdx) in cardWidth[1]"
                                :key="tIdx"
                                class="h-[40rpx] bg-[#F3F4F6] rounded-full animate-pulse"
                                :style="`width:${tagWidth}rpx`"></view>
                        </view>
                    </view>
                </view>
            </view>
        </template>

        <template v-else-if="isPreviewMode && !displayTemplate">
            <view class="flex flex-col items-center justify-center pt-[240rpx] px-[60rpx]">
                <view
                    class="w-[240rpx] h-[240rpx] bg-white rounded-full flex items-center justify-center mb-[48rpx] shadow-sm border border-[#F3F4F6]">
                    <view class="w-[180rpx] h-[180rpx] bg-[#F3F4F6] rounded-full flex items-center justify-center">
                        <u-icon name="calendar" color="#9CA3AF" size="80" />
                    </view>
                </view>
                <text class="text-[36rpx] text-[#111827] font-extrabold mb-[20rpx]">暂无工作流模板</text>
                <text class="text-[28rpx] text-[#6B7280] text-center mb-[80rpx] leading-relaxed">
                    您还没有配置任何自动任务工作流<br />请前往模板市场选择或创建专属模板
                </text>
                <view
                    class="bg-primary flex items-center justify-center gap-[12rpx] px-[80rpx] py-[32rpx] rounded-full shadow-lg shadow-primary/30 active:scale-95 transition-transform"
                    @click="goToMarket">
                    <u-icon name="plus" color="#ffffff" size="32" />
                    <text class="text-white text-[32rpx] font-bold">前往模板市场</text>
                </view>
            </view>
        </template>

        <template v-else>
            <view class="bg-white px-[30rpx] pt-[16rpx] pb-[32rpx] rounded-b-[32rpx] shadow-sm">
                <view
                    v-if="showReadonlyTip"
                    class="bg-[#FFF7ED] border border-[#FFEDD5] rounded-[24rpx] p-[24rpx] mb-[24rpx] flex items-center justify-between shadow-sm">
                    <view class="flex items-center gap-[16rpx] flex-1">
                        <view
                            class="w-[64rpx] h-[64rpx] rounded-full bg-[#FFEDD5] flex items-center justify-center flex-shrink-0">
                            <u-icon name="lock-fill" color="#F97316" size="32" />
                        </view>
                        <view class="flex flex-col justify-center">
                            <text class="text-[28rpx] text-[#9A3412] font-extrabold mb-[4rpx]">
                                {{ isDisplaySystem ? "系统模板" : "IP专属" }}不可编辑
                            </text>
                            <text class="text-[22rpx] text-[#D97706]">克隆后即可自定义排期任务</text>
                        </view>
                    </view>
                    <view
                        class="flex items-center gap-[8rpx] px-[32rpx] py-[16rpx] rounded-full shadow-md shadow-[#f97316]/20 active:scale-95 transition-transform flex-shrink-0 ml-[20rpx]"
                        style="background: linear-gradient(135deg, #f97316 0%, #ea580c 100%)"
                        @click="handleClone">
                        <u-icon name="copy" color="#ffffff" size="28" />
                        <text class="text-[26rpx] text-white font-bold">立即克隆</text>
                    </view>
                </view>

                <view
                    class="relative overflow-hidden rounded-[24rpx] px-[32rpx] py-[28rpx] active:scale-[0.98] transition-all duration-200 shadow-sm"
                    :style="
                        useDarkWorkflowCard
                            ? 'background:linear-gradient(135deg,#1f2937 0%,#111827 100%);border:2rpx solid #374151;'
                            : 'background:linear-gradient(135deg,#F0F5FF 0%,#E0EAFF 100%);border:2rpx solid #C5D9FF;'
                    "
                    @click="goToMarket()">
                    <view
                        v-if="useDarkWorkflowCard"
                        class="absolute right-[-40rpx] top-[-60rpx] w-[240rpx] h-[240rpx] rounded-full"
                        style="background: rgba(255, 255, 255, 0.04)" />
                    <view class="relative z-[1] flex items-center justify-between">
                        <view class="flex-1 min-w-0 relative z-[1]">
                            <view class="flex items-center gap-[12rpx] mb-[16rpx] flex-wrap">
                                <view
                                    class="px-[16rpx] py-[6rpx] rounded-[12rpx]"
                                    :class="useDarkWorkflowCard ? '' : 'bg-[#DBEAFE]'"
                                    :style="useDarkWorkflowCard ? 'background:rgba(255,255,255,0.16);' : ''">
                                    <text
                                        class="text-[22rpx] font-bold"
                                        :class="useDarkWorkflowCard ? 'text-white' : 'text-[#2563EB]'"
                                        :style="useDarkWorkflowCard ? 'opacity:0.85;' : ''">
                                        {{
                                            isPreviewMode ? "模板预览" : displayTemplate ? "当前工作流" : "自定义工作流"
                                        }}
                                    </text>
                                </view>
                                <focus-badge :focus="displayTemplate?.focus ?? FocusEnum.GENERAL" />
                                <view
                                    v-if="displayTemplate"
                                    class="inline-flex items-center gap-[8rpx] px-[16rpx] py-[6rpx] rounded-[12rpx]"
                                    :class="templateUsageStatusClass"
                                    :style="templateUsageStatusStyle">
                                    <view
                                        class="w-[10rpx] h-[10rpx] rounded-full"
                                        :style="{ backgroundColor: isDisplayTemplateInUse ? '#22C55E' : '#F59E0B' }" />
                                    <text class="text-[22rpx] font-bold">{{ templateUsageStatusText }}</text>
                                </view>
                            </view>
                            <view
                                class="text-[30rpx] font-extrabold tracking-wide"
                                :class="useDarkWorkflowCard ? 'text-white' : 'text-[#1E3A5F]'">
                                {{ displayTemplate?.name || DEFAULT_TEMPLATE_NAME }}
                            </view>
                            <view
                                v-if="!isPreviewMode"
                                class="inline-flex items-center gap-[10rpx] mt-[16rpx] px-[22rpx] py-[8rpx] rounded-full border border-solid active:opacity-80"
                                style="background: rgba(255, 255, 255, 0.16); border-color: rgba(255, 255, 255, 0.28)"
                                @click.stop="goToMarket">
                                <image
                                    src="@/ai_modules/person/static/icons/repeat-white.svg"
                                    class="w-[28rpx] h-[28rpx]"
                                    mode="aspectFit" />
                                <text class="text-[24rpx] font-bold text-white">切换工作流</text>
                            </view>
                        </view>
                        <view
                            class="w-[72rpx] h-[72rpx] flex items-center justify-center rounded-full flex-shrink-0 ml-[20rpx] shadow-sm relative z-[1]"
                            :class="useDarkWorkflowCard ? '' : 'bg-[#ffffff]'"
                            :style="useDarkWorkflowCard ? 'background:rgba(255,255,255,0.14);' : ''">
                            <u-icon name="arrow-right" :color="useDarkWorkflowCard ? '#ffffff' : '#0065fb'" size="28" />
                        </view>
                    </view>
                </view>
            </view>

            <view class="px-[30rpx] mt-4">
                <view class="mt-[32rpx]">
                    <view
                        v-if="displayTasks.length === 0"
                        class="flex flex-col items-center justify-center py-[100rpx] px-[40rpx] bg-white rounded-[32rpx] border-2 border-dashed border-[#E5E7EB] mt-[20rpx]">
                        <view
                            class="w-[140rpx] h-[140rpx] bg-[#F9FAFB] rounded-full flex items-center justify-center mb-[32rpx]">
                            <u-icon name="list" color="#D1D5DB" size="64" />
                        </view>
                        <text class="text-[32rpx] text-[#4B5563] font-bold mb-[16rpx]">暂无排期任务</text>
                        <text class="text-[26rpx] text-[#9CA3AF] text-center leading-relaxed">
                            <template v-if="!canEditTasks"> 该模板尚未配置任何任务<br />请克隆后自由添加 </template>
                            <template v-else> 当前工作流空空如也<br />点击下方「添加任务」开始配置吧 </template>
                        </text>
                    </view>

                    <template v-else>
                        <task-group
                            ref="morningGroupRef"
                            title="早间任务"
                            time-range="00:00 - 12:00"
                            dot-color="#FB923C"
                            :tasks="morningTasks"
                            :is-editable="canEditTasks"
                            :collapsed="collapsedGroups.morning"
                            @delete="handleDeleteTask"
                            @demo="handleDemoTask"
                            @edit="handleEditTask"
                            @toggle-status="handleToggleStatus"
                            @toggle="toggleGroup('morning')" />

                        <task-group
                            ref="afternoonGroupRef"
                            title="午后任务"
                            time-range="12:00 - 18:00"
                            dot-color="#60A5FA"
                            :tasks="afternoonTasks"
                            :is-editable="canEditTasks"
                            :collapsed="collapsedGroups.afternoon"
                            @delete="handleDeleteTask"
                            @demo="handleDemoTask"
                            @edit="handleEditTask"
                            @toggle-status="handleToggleStatus"
                            @toggle="toggleGroup('afternoon')" />

                        <task-group
                            ref="eveningGroupRef"
                            title="晚间任务"
                            time-range="18:00 - 24:00"
                            dot-color="#C084FC"
                            :tasks="eveningTasks"
                            :is-editable="canEditTasks"
                            :collapsed="collapsedGroups.evening"
                            @delete="handleDeleteTask"
                            @demo="handleDemoTask"
                            @edit="handleEditTask"
                            @toggle-status="handleToggleStatus"
                            @toggle="toggleGroup('evening')" />
                    </template>
                </view>
            </view>
        </template>

        <view
            v-if="showPreviewFooter"
            class="fixed bottom-[60rpx] left-0 right-0 flex justify-center gap-[20rpx] px-[30rpx] z-10">
            <view
                v-if="showPreviewApplyButton"
                class="flex items-center justify-center gap-[8rpx] px-[40rpx] py-[28rpx] rounded-full shadow-xl active:opacity-80 transition-opacity"
                :class="
                    isPreviewEditableTemplate ? 'bg-white border border-[#E5E7EB]' : 'bg-primary shadow-[#0065fb]/30'
                "
                @click="handleApplyTemplate">
                <u-icon name="checkbox-mark" :color="isPreviewEditableTemplate ? '#2563EB' : '#ffffff'" size="32" />
                <text
                    class="text-[28rpx] font-bold"
                    :class="isPreviewEditableTemplate ? 'text-[#2563EB]' : 'text-white'">
                    套用模板
                </text>
            </view>
            <view
                v-if="showPreviewAddButton"
                class="bg-primary flex items-center justify-center gap-[8rpx] px-[40rpx] py-[28rpx] rounded-full shadow-xl shadow-[#0065fb]/30 active:opacity-80 transition-opacity"
                @click="openAddTaskModal">
                <u-icon name="plus" color="#ffffff" size="32" />
                <text class="text-white text-[28rpx] font-bold">添加任务</text>
            </view>
        </view>

        <view v-if="showCurrentAddButton" class="fixed bottom-[60rpx] left-0 right-0 flex justify-center z-10">
            <view
                class="bg-primary flex items-center gap-[8rpx] px-[48rpx] py-[28rpx] rounded-full shadow-xl active:opacity-80 transition-opacity"
                @click="openAddTaskModal">
                <u-icon name="plus" color="#ffffff" size="32" />
                <text class="text-white text-[28rpx] font-bold">添加任务</text>
            </view>
        </view>
    </view>

    <u-popup v-model="showChooseDevice" mode="bottom" border-radius="40" :safe-area-inset-bottom="true">
        <view class="bg-[#F4F6FB] px-[32rpx] pt-[32rpx] pb-[48rpx]">
            <view class="flex justify-center mb-[32rpx]">
                <view class="w-[80rpx] h-[8rpx] rounded-full bg-gray-200" />
            </view>

            <view class="flex items-center gap-x-[12rpx] mb-[8rpx]">
                <view class="w-[8rpx] h-[36rpx] bg-primary rounded-full"></view>
                <text class="text-[32rpx] font-bold text-[#212121]">选择设备</text>
            </view>
            <text class="text-[24rpx] text-[#676767] block mb-[32rpx] ml-[20rpx]">请选择您要演示的设备</text>

            <view class="flex flex-col gap-[16rpx] mb-[40rpx] max-h-[640rpx] overflow-y-auto">
                <view
                    v-for="device in demoDeviceList"
                    :key="device.device_code"
                    class="bg-white rounded-[28rpx] px-[28rpx] py-[24rpx] flex items-center gap-x-[20rpx] shadow-sm border transition-all"
                    :class="
                        selectedDemoDevice?.device_code === device.device_code
                            ? 'border-primary bg-[#EEF4FF] shadow-[0_2rpx_12rpx_rgba(59,130,246,0.15)]'
                            : 'border-[#f9f9f9]'
                    "
                    @click="selectDemoDevice(device)">
                    <view
                        class="w-[72rpx] h-[72rpx] rounded-[22rpx] bg-[#EBF2FF] flex items-center justify-center flex-shrink-0">
                        <image
                            src="/static/images/icons/device_primary.svg"
                            class="w-[36rpx] h-[36rpx]"
                            mode="aspectFit" />
                    </view>
                    <view class="flex-1 min-w-0">
                        <view class="flex items-center gap-[10rpx] mb-[8rpx]">
                            <text
                                class="text-[28rpx] font-bold truncate"
                                :class="
                                    selectedDemoDevice?.device_code === device.device_code
                                        ? 'text-primary'
                                        : 'text-[#1F2937]'
                                ">
                                {{ getDemoDeviceName(device) }}
                            </text>
                            <view
                                class="px-[12rpx] py-[4rpx] rounded-full flex items-center gap-[6rpx] flex-shrink-0"
                                :style="{ backgroundColor: `${getDemoDeviceStatus(device.status).color}14` }">
                                <view
                                    class="w-[10rpx] h-[10rpx] rounded-full"
                                    :style="{ backgroundColor: getDemoDeviceStatus(device.status).color }" />
                                <text
                                    class="text-[20rpx] font-semibold"
                                    :style="{ color: getDemoDeviceStatus(device.status).color }">
                                    {{ getDemoDeviceStatus(device.status).label }}
                                </text>
                            </view>
                        </view>
                        <text class="text-[22rpx] text-[#64748B] font-mono truncate">
                            {{ device.device_code }}
                        </text>
                    </view>
                    <view
                        class="w-[40rpx] h-[40rpx] rounded-full flex items-center justify-center flex-shrink-0"
                        :class="selectedDemoDevice?.device_code === device.device_code ? 'bg-primary' : 'bg-[#f9f9f9]'">
                        <u-icon
                            name="checkmark"
                            :color="selectedDemoDevice?.device_code === device.device_code ? '#ffffff' : '#CBD5E1'"
                            size="22" />
                    </view>
                </view>
            </view>

            <view class="flex gap-[16rpx]">
                <view
                    class="flex-1 h-[96rpx] rounded-full bg-white border border-solid border-[#f9f9f9] flex items-center justify-center shadow-sm active:opacity-70"
                    @click="showChooseDevice = false">
                    <text class="text-[28rpx] font-bold text-[#676767]">取消</text>
                </view>
                <view
                    class="flex-[2] h-[96rpx] rounded-full bg-primary flex items-center justify-center shadow-md active:opacity-90"
                    @click="confirmDeviceSelection">
                    <text class="text-[28rpx] font-bold text-white">确认选择</text>
                </view>
            </view>
        </view>
    </u-popup>

    <u-popup v-model="showChooseApp" mode="bottom" border-radius="40" :safe-area-inset-bottom="true">
        <view class="bg-[#F4F6FB] px-[32rpx] pt-[32rpx] pb-[48rpx]">
            <view class="flex justify-center mb-[32rpx]">
                <view class="w-[80rpx] h-[8rpx] rounded-full bg-gray-200" />
            </view>

            <view class="flex items-center gap-x-[12rpx] mb-[8rpx]">
                <view class="w-[8rpx] h-[36rpx] bg-primary rounded-full"></view>
                <text class="text-[32rpx] font-bold text-[#212121]">选择平台</text>
            </view>
            <text class="text-[24rpx] text-[#676767] block mb-[32rpx] ml-[20rpx]">请选择您要演示的平台</text>

            <view class="flex flex-col gap-[16rpx] mb-[40rpx]">
                <view
                    v-for="platform in chooseAppPlatforms"
                    :key="`${platform.type}-${platform.name}`"
                    class="bg-white rounded-full px-[28rpx] py-[20rpx] flex items-center gap-x-[20rpx] shadow-sm border transition-all"
                    :class="
                        selectedPlatform?.type === platform.type
                            ? 'border-primary bg-[#EEF4FF] shadow-[0_2rpx_12rpx_rgba(59,130,246,0.15)]'
                            : 'border-[#f9f9f9]'
                    "
                    @click="selectPlatform(platform)">
                    <view
                        class="w-[48rpx] h-[48rpx] rounded-full flex items-center justify-center flex-shrink-0"
                        :style="{ backgroundColor: `${platform.color}18` }">
                        <view class="w-[18rpx] h-[18rpx] rounded-full" :style="{ backgroundColor: platform.color }" />
                    </view>
                    <text
                        class="flex-1 text-[28rpx] font-semibold"
                        :class="selectedPlatform?.type === platform.type ? 'text-primary' : 'text-[#424242]'">
                        {{ platform.name }}
                    </text>
                    <view
                        class="w-[40rpx] h-[40rpx] rounded-full flex items-center justify-center flex-shrink-0"
                        :class="selectedPlatform?.type === platform.type ? 'bg-primary' : 'bg-[#f9f9f9]'">
                        <u-icon
                            name="checkmark"
                            :color="selectedPlatform?.type === platform.type ? '#ffffff' : '#CBD5E1'"
                            size="22" />
                    </view>
                </view>
            </view>

            <view class="flex gap-[16rpx]">
                <view
                    class="flex-1 h-[96rpx] rounded-full bg-white border border-solid border-[#f9f9f9] flex items-center justify-center shadow-sm active:opacity-70"
                    @click="showChooseApp = false">
                    <text class="text-[28rpx] font-bold text-[#676767]">取消</text>
                </view>
                <view
                    class="flex-[2] h-[96rpx] rounded-full bg-primary flex items-center justify-center shadow-md active:opacity-90"
                    @click="confirmSelection">
                    <text class="text-[28rpx] font-bold text-white">确认选择</text>
                </view>
            </view>
        </view>
    </u-popup>

    <view
        v-if="showAddTaskModal"
        class="fixed inset-0 bg-[#000000]/60 z-50 flex items-center justify-center px-[40rpx]"
        @click.stop="closeAddTaskModal">
        <view class="bg-white rounded-[32rpx] w-full p-[48rpx] shadow-2xl" @click.stop>
            <view class="flex items-center justify-between mb-[40rpx]">
                <text class="text-[34rpx] font-extrabold text-[#111827]">
                    {{ editingTaskId !== null ? "编辑任务节点" : "添加任务节点" }}
                </text>
                <view
                    class="w-[56rpx] h-[56rpx] flex items-center justify-center bg-[#F3F4F6] rounded-full"
                    @click.stop="closeAddTaskModal">
                    <u-icon name="close" color="#6B7280" size="28" />
                </view>
            </view>

            <view
                v-if="addTaskError"
                class="flex items-start gap-[8rpx] bg-[#FEF2F2] px-[24rpx] py-[20rpx] rounded-[16rpx] mb-[28rpx]">
                <text class="shrink-0">
                    <u-icon name="error-circle" color="#DC2626" size="24" />
                </text>
                <text class="text-[22rpx] text-[#DC2626]">{{ addTaskError }}</text>
            </view>

            <view class="flex flex-col gap-[28rpx]">
                <view>
                    <text class="text-[24rpx] font-bold text-[#374151] mb-[12rpx] block">任务类型</text>
                    <picker
                        mode="selector"
                        :range="modalTaskLabelList"
                        :value="taskTypeIndex"
                        @change="handleTaskTypeChange">
                        <view
                            class="w-full bg-[#F9FAFB] border border-[#E5E7EB] rounded-[20rpx] px-[24rpx] py-[24rpx] flex items-center justify-between">
                            <text class="text-[26rpx] text-[#111827]">{{
                                modalTaskConfigList.find((c) => c.scene === newTask.scene)?.label ||
                                taskStore.getTaskConfigByScene(newTask.scene)?.label ||
                                "请选择"
                            }}</text>
                            <u-icon name="arrow-down" color="#9CA3AF" size="24" />
                        </view>
                    </picker>
                </view>

                <view>
                    <text class="text-[24rpx] font-bold text-[#374151] mb-[12rpx] block">
                        执行平台
                        <text class="text-[22rpx] text-[#9CA3AF] font-normal">（按点击顺序排列）</text>
                    </text>
                    <view class="flex flex-wrap gap-[16rpx]">
                        <view
                            v-for="(p, pIdx) in availablePlatforms"
                            :key="pIdx"
                            class="flex items-center gap-[8rpx] px-[24rpx] py-[14rpx] rounded-[16rpx] border text-[24rpx] font-medium transition-colors"
                            :class="
                                newTask.platforms.includes(p.name)
                                    ? 'bg-[#EFF6FF] border-[#3B82F6] text-[#2563EB]'
                                    : 'bg-[#F9FAFB] border-[#E5E7EB] text-[#4B5563]'
                            "
                            @click="togglePlatform(p.name)">
                            <text>{{ p.name }}</text>
                            <view
                                v-if="newTask.platforms.includes(p.name)"
                                class="w-[32rpx] h-[32rpx] bg-[#3B82F6] rounded-full flex items-center justify-center">
                                <text class="text-white text-[18rpx] font-bold">
                                    {{ newTask.platforms.indexOf(p.name) + 1 }}
                                </text>
                            </view>
                        </view>
                    </view>
                    <view
                        v-if="newTask.scene === TaskScene.COMMENT_RECEIVE && newTask.platforms.includes('视频号')"
                        class="flex items-start gap-[8rpx] bg-[#FFF7ED] px-[24rpx] py-[20rpx] rounded-[16rpx] mt-[12rpx]">
                        <u-icon name="info-circle" color="#F97316" size="24" class="flex-shrink-0" />
                        <text class="text-[22rpx] text-[#F97316]">视频号仅能点赞感谢</text>
                    </view>
                </view>

                <view class="flex gap-[24rpx]">
                    <view class="flex-1">
                        <text class="text-[24rpx] font-bold text-[#374151] mb-[12rpx] block">
                            开始时间
                            <text class="text-[20rpx] text-[#9CA3AF] font-normal ml-[4rpx]">(06:00起)</text>
                        </text>
                        <picker
                            mode="time"
                            :value="newTask.startTime"
                            start="06:00"
                            end="23:59"
                            @change="handleStartTimeChange">
                            <view
                                class="w-full bg-[#F9FAFB] border border-solid border-[#E5E7EB] rounded-[20rpx] px-[24rpx] py-[24rpx] flex items-center justify-between">
                                <text class="text-[26rpx] text-[#111827]">{{ newTask.startTime || "请选择" }}</text>
                                <u-icon name="clock" color="#9CA3AF" size="24" />
                            </view>
                        </picker>
                    </view>
                    <view class="flex-1">
                        <text class="text-[24rpx] font-bold text-[#374151] mb-[12rpx] block">结束时间</text>
                        <picker
                            mode="time"
                            :value="newTask.endTime"
                            :start="newTask.startTime || '06:00'"
                            end="23:59"
                            :disabled="isEndTimeDisabled"
                            @change="handleEndTimeChange">
                            <view
                                class="w-full rounded-[20rpx] px-[24rpx] py-[24rpx] flex items-center justify-between border-[2rpx] border-solid"
                                :class="
                                    isEndTimeDisabled
                                        ? 'bg-[#F3F4F6] border-[transparent]'
                                        : 'bg-[#F9FAFB] border-[#E5E7EB]'
                                ">
                                <text
                                    class="text-[26rpx]"
                                    :class="isEndTimeDisabled ? 'text-[#9CA3AF]' : 'text-[#111827]'">
                                    {{ newTask.endTime || "请选择" }}
                                </text>
                                <u-icon name="clock" color="#9CA3AF" size="24" />
                            </view>
                        </picker>
                    </view>
                </view>

                <view
                    v-if="isEndTimeDisabled"
                    class="flex items-start gap-[8rpx] bg-[#EFF6FF] px-[24rpx] py-[20rpx] rounded-[16rpx]">
                    <u-icon name="info-circle" color="#3B82F6" size="24" class="flex-shrink-0" />
                    <text class="text-[22rpx] text-[#3B82F6]">{{ timeHintText }}</text>
                </view>
            </view>

            <view
                class="w-full rounded-[20rpx] py-[28rpx] mt-[40rpx] flex items-center justify-center active:opacity-80 transition-opacity"
                :class="isSaving ? 'bg-[#9CA3AF]' : 'bg-primary'"
                @click="handleSaveTask">
                <u-loading v-if="isSaving" mode="circle" color="#ffffff" size="28" />
                <text v-else class="text-white text-[28rpx] font-extrabold">
                    {{ editingTaskId !== null ? "确认修改" : "确认添加" }}
                </text>
            </view>
        </view>
    </view>

    <confirm-dialog
        v-model="showConfirmDemoDialog"
        title="提示"
        content="当前暂无真实数据，将使用模拟数据进行演示。模拟数据仅用于展示效果，不会影响后续实际使用。是否确认进入演示模式?"
        @confirm="startDemoTask" />
</template>

<script setup lang="ts">
import FocusBadge from "@/ai_modules/person/components/focus-badge/focus-badge.vue";
import { checkRealTask, createDemoTask } from "@/api/device";
import { getPersonDetail, getPersonDeviceList } from "@/api/person";
import { AppTypeEnum, PersonTypeEnum } from "@/enums/appEnums";
import TaskGroup from "./components/task-group.vue";
import {
    useTaskStore,
    FocusEnum,
    TemplateTypeEnum,
    timeToMins,
    minsToTime,
    getPlatformName,
    type PlatformOption,
    type TaskItem,
    TaskScene,
} from "./stores/taskStore";

// ─── 本地常量（对齐 taskStore 风格）──────────────────────────
const SCENE_VIDEO_PUBLISH = 5;
const MINUTES_PER_PLATFORM = 10;
const FORBIDDEN_ZONE_END_MINS = 0; // 06:00 禁止排期区（分钟）
const DEFAULT_START_TIME = "08:00";
const DEFAULT_END_TIME = minsToTime(timeToMins(DEFAULT_START_TIME) + MINUTES_PER_PLATFORM); // "08:10"
const DEFAULT_TEMPLATE_NAME = "自定义任务流";
const DEFAULT_PERSON_TYPE = PersonTypeEnum.PERSONAL_IP;

const PLATFORM_COLOR_MAP: Record<number, string> = {
    [AppTypeEnum.XHS]: "#FF2442",
    [AppTypeEnum.DOUYIN]: "#111827",
    [AppTypeEnum.KUAISHOU]: "#FF6E2E",
    [AppTypeEnum.WECHAT]: "#07C160",
};

const taskStore = useTaskStore();
const {
    currentTemplate,
    currentTasks,
    isLoading,
    isReadOnly,
    previewTemplate,
    previewTasks,
    selectableTaskConfigList,
} = storeToRefs(taskStore);

// ─── 骨架屏数据 ────────────────────────────────────────────────
const skeletonGroups: [number, number[]][][] = [
    [
        [160, [80, 80]],
        [140, [80]],
        [180, [80, 80]],
    ],
    [
        [150, [80, 80]],
        [120, [80]],
    ],
    [
        [170, [80]],
        [140, [80, 80]],
    ],
];

// ─── 页面状态 ──────────────────────────────────────────────────
const personId = ref<string>("");
const previewTemplateId = ref<string>("");
const isSaving = ref<boolean>(false);
const showAddTaskModal = ref<boolean>(false);
const addTaskError = ref<string>("");
const taskTypeIndex = ref<number>(0);
const initialized = ref<boolean>(false);
const editingTaskId = ref<number | null>(null);
const demoPersonaType = ref<number>(DEFAULT_PERSON_TYPE);
const showChooseDevice = ref<boolean>(false);
const showChooseApp = ref<boolean>(false);
const showConfirmDemoDialog = ref<boolean>(false);
const demoDeviceList = ref<any[]>([]);
const selectedDemoDevice = ref<any>(null);
const chooseAppPlatforms = ref<any[]>([]);
const selectedPlatform = ref<any>(null);
const pendingDemoTask = ref<TaskItem | null>(null);
const demoParams = ref<Record<string, any>>({});
let demoContextPromise: Promise<void> | null = null;
let suppressTaskEditUntil = 0;

const newTask = reactive({
    scene: TaskScene.COMMENT_GET_CUSTOMER as number,
    platforms: ["抖音"] as string[],
    startTime: DEFAULT_START_TIME,
    endTime: DEFAULT_END_TIME,
});

// ─── 是否预览模式 ──────────────────────────────────────────────
const isPreviewMode = computed(() => !!previewTemplateId.value);

// ─── 统一展示数据（预览模式走 preview，否则走 current）────────
const displayTemplate = computed(() => (isPreviewMode.value ? previewTemplate.value : currentTemplate.value));
const displayTasks = computed(() => (isPreviewMode.value ? previewTasks.value : currentTasks.value));
const activeTemplate = computed(() => (isPreviewMode.value ? taskStore.previewTemplate : currentTemplate.value));
const useDarkWorkflowCard = computed(() => !isPreviewMode.value || isReadOnly.value);
const isDisplayReadOnly = computed(
    () =>
        displayTemplate.value?.type === TemplateTypeEnum.FIXED ||
        displayTemplate.value?.type === TemplateTypeEnum.SYSTEM,
);
const isDisplaySystem = computed(() => displayTemplate.value?.type === TemplateTypeEnum.SYSTEM);
const isPreviewCurrentTemplate = computed(
    () => isPreviewMode.value && !!previewTemplateId.value && previewTemplateId.value === taskStore.currentTemplateId,
);
const isDisplayTemplateInUse = computed(
    () => !!displayTemplate.value?.id && displayTemplate.value.id === taskStore.currentTemplateId,
);
const templateUsageStatusText = computed(() => (isDisplayTemplateInUse.value ? "使用中" : "未使用"));
const templateUsageStatusClass = computed(() => {
    if (useDarkWorkflowCard.value) return isDisplayTemplateInUse.value ? "text-[#BBF7D0]" : "text-[#FDE68A]";
    return isDisplayTemplateInUse.value ? "bg-[#DCFCE7] text-[#16A34A]" : "bg-[#FEF3C7] text-[#D97706]";
});
const templateUsageStatusStyle = computed(() =>
    useDarkWorkflowCard.value
        ? isDisplayTemplateInUse.value
            ? "background:rgba(34,197,94,0.16);"
            : "background:rgba(245,158,11,0.18);"
        : "",
);
const isPreviewEditableTemplate = computed(
    () => isPreviewMode.value && !!previewTemplate.value && !isDisplayReadOnly.value,
);
const canModifyCurrentTemplate = computed(
    () => !isPreviewMode.value && !!currentTemplate.value && !isDisplayReadOnly.value,
);
const canEditTasks = computed(() => !!activeTemplate.value && !isDisplayReadOnly.value);
const showReadonlyTip = computed(() => isDisplayReadOnly.value);
const isFooterHidden = computed(
    () => showAddTaskModal.value || showChooseDevice.value || showChooseApp.value || showConfirmDemoDialog.value,
);
const showPreviewFooter = computed(
    () =>
        isPreviewMode.value &&
        !!activeTemplate.value &&
        (showPreviewApplyButton.value || showPreviewAddButton.value) &&
        !isLoading.value &&
        !isFooterHidden.value,
);
const showPreviewApplyButton = computed(
    () => isPreviewMode.value && !!activeTemplate.value && !isPreviewCurrentTemplate.value,
);
const showPreviewAddButton = computed(() => isPreviewEditableTemplate.value);
const showCurrentAddButton = computed(
    () => canModifyCurrentTemplate.value && !isLoading.value && !isFooterHidden.value,
);

// ─── 计算属性 ──────────────────────────────────────────────────
const morningTasks = computed(() =>
    displayTasks.value.filter((t) => {
        const mins = timeToMins(t.time.split("-")[0]);
        return mins >= FORBIDDEN_ZONE_END_MINS && mins < 720;
    }),
);
const afternoonTasks = computed(() =>
    displayTasks.value.filter((t) => {
        const mins = timeToMins(t.time.split("-")[0]);
        return mins >= 720 && mins < 1080;
    }),
);
const eveningTasks = computed(() =>
    displayTasks.value.filter((t) => {
        const mins = timeToMins(t.time.split("-")[0]);
        return mins >= 1080;
    }),
);

/** 添加：仅可添加类型；编辑：可添加类型 + 当前已关闭类型（仅当前弹窗临时合并） */
const modalTaskConfigList = computed(() => {
    if (editingTaskId.value !== null) {
        return taskStore.getEditSelectableTaskConfigList(newTask.scene);
    }
    return selectableTaskConfigList.value;
});

const modalTaskLabelList = computed(() => modalTaskConfigList.value.map((c) => c.label));

const availablePlatforms = computed<PlatformOption[]>(() => {
    return (
        modalTaskConfigList.value.find((c) => c.scene === newTask.scene)?.platforms ??
        taskStore.getTaskConfigByScene(newTask.scene)?.platforms ??
        []
    );
});

const resetNewTaskToFirstSelectable = () => {
    const first = selectableTaskConfigList.value[0];
    if (!first) {
        newTask.scene = TaskScene.COMMENT_GET_CUSTOMER;
        newTask.platforms = [];
        taskTypeIndex.value = 0;
        return false;
    }
    newTask.scene = first.scene;
    newTask.platforms = first.platforms[0] ? [first.platforms[0].name] : [];
    taskTypeIndex.value = 0;
    return true;
};

const isEndTimeDisabled = computed(() => newTask.platforms.length > 1 || newTask.scene === SCENE_VIDEO_PUBLISH);

const timeHintText = computed(() => {
    if (newTask.platforms.length > 1) {
        return `已选择 ${newTask.platforms.length} 个平台，时长自动锁定为 ${
            newTask.platforms.length * MINUTES_PER_PLATFORM
        } 分钟`;
    }
    return `视频发布任务时长已自动锁定为 ${MINUTES_PER_PLATFORM} 分钟`;
});

// ─── 分组折叠 ──────────────────────────────────────────────────
const collapsedGroups = reactive<Record<string, boolean>>({
    morning: false,
    afternoon: false,
    evening: false,
});
const toggleGroup = (key: string) => {
    collapsedGroups[key] = !collapsedGroups[key];
};

const goToMarket = () => {
    uni.$u.route({
        url: "/ai_modules/person/pages/task_flow_template/task_flow_template",
        type: "redirect",
        params: { id: personId.value },
    });
};

// ─── 克隆模板 ──────────────────────────────────────────────────
const handleClone = () => {
    uni.showModal({
        title: "克隆模板",
        content: `将克隆「${displayTemplate.value?.name}」并切换使用，是否继续？`,
        confirmColor: "#F97316",
        success: async ({ confirm }) => {
            if (!confirm) return;
            try {
                await taskStore.cloneTemplate(isPreviewMode.value ? "preview" : "current");
                uni.redirectTo({
                    url: `/ai_modules/person/pages/task_flow/task_flow?id=${personId.value}`,
                });
                uni.showToast({ title: "克隆成功", icon: "none", duration: 3000 });
            } catch (error: any) {
                uni.showToast({ title: error || "克隆失败，请重试", icon: "none", duration: 3000 });
            }
        },
    });
};

// ─── 套用模板 ──────────────────────────────────────────────────
const handleApplyTemplate = async () => {
    if (isPreviewCurrentTemplate.value) {
        uni.showToast({ title: "该模板已在使用中", icon: "none", duration: 2000 });
        return;
    }
    uni.showLoading({ title: "套用中...", mask: true });
    try {
        taskStore.currentTemplateId = previewTemplateId.value;
        await taskStore.useCurrentTemplate();
        uni.redirectTo({
            url: `/ai_modules/person/pages/task_flow/task_flow?id=${personId.value}`,
        });
        uni.hideLoading();
        uni.showToast({ title: "套用成功", icon: "none", duration: 2000 });
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error || "套用失败，请重试", icon: "none", duration: 3000 });
    }
};

// ─── 任务操作 ──────────────────────────────────────────────────
const showReadonlyTaskToast = () => {
    uni.showToast({ title: "该模板不可编辑，请克隆后再修改", icon: "none", duration: 2500 });
};

const isDefaultLockedTask = (task?: TaskItem | null) => Number(task?.is_default) === 1;

const findDisplayTaskById = (id: number) => displayTasks.value.find((task) => task.id === id);

const handleDeleteTask = (id: number) => {
    if (!canEditTasks.value) {
        showReadonlyTaskToast();
        return;
    }
    if (isDefaultLockedTask(findDisplayTaskById(id))) return;
    uni.showModal({
        title: "删除任务",
        content: "确定删除该任务节点吗？删除后将从当前任务流中移除。",
        confirmColor: "#E11D48",
        success: async ({ confirm }) => {
            if (!confirm) return;
            uni.showLoading({ title: "删除中...", mask: true });
            try {
                if (isPreviewEditableTemplate.value) {
                    await taskStore.deletePreviewTask(previewTemplateId.value, id);
                } else {
                    await taskStore.deleteTask(id);
                }
                uni.hideLoading();
                uni.showToast({ title: "删除成功", icon: "none", duration: 2000 });
            } catch (error: any) {
                uni.hideLoading();
                uni.showToast({ title: error || "删除失败，请重试", icon: "none", duration: 3000 });
            }
        },
    });
};

const handleEditTask = (task: TaskItem) => {
    if (Date.now() < suppressTaskEditUntil) return;
    if (isDefaultLockedTask(task)) return;
    if (!canEditTasks.value) {
        showReadonlyTaskToast();
        return;
    }
    const editOptions = taskStore.getEditSelectableTaskConfigList(task.scene, task.title);
    const idx = editOptions.findIndex((c) => c.scene === task.scene);
    taskTypeIndex.value = idx >= 0 ? idx : 0;
    newTask.scene = task.scene;

    const sceneConfig = editOptions.find((c) => c.scene === task.scene) ?? taskStore.getTaskConfigByScene(task.scene);
    const sortedPlatforms = [...task.platformRaw]
        .sort((a, b) => a.order - b.order)
        .map((p) => {
            const cfg = sceneConfig?.platforms ?? [];
            return cfg.find((c) => c.type == p.account_type)?.name ?? "";
        })
        .filter(Boolean);

    newTask.platforms = sortedPlatforms.length
        ? sortedPlatforms
        : sceneConfig?.platforms?.[0]
        ? [sceneConfig.platforms[0].name]
        : [];

    const [start, end] = task.time.split("-");
    newTask.startTime = start;
    newTask.endTime = end;

    editingTaskId.value = task.id;
    addTaskError.value = "";
    showAddTaskModal.value = true;
};

const handleToggleStatus = async (id: number, newStatus: number) => {
    if (!canEditTasks.value) {
        showReadonlyTaskToast();
        return;
    }
    if (isDefaultLockedTask(findDisplayTaskById(id))) return;
    uni.showLoading({ title: "操作中...", mask: true });
    try {
        if (isPreviewEditableTemplate.value) {
            await taskStore.togglePreviewTaskStatus(previewTemplateId.value, id, newStatus === 1 ? 0 : 1);
        } else {
            await taskStore.toggleTaskStatus(id, newStatus === 1 ? 0 : 1);
        }
        uni.hideLoading();
        uni.showToast({ title: "操作成功", icon: "none", duration: 3000 });
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error || "操作失败，请重试", icon: "none", duration: 3000 });
    }
};

// ─── 立即演示 ─────────────────────────────────────────────────
const getTaskPlatformOptions = (task: TaskItem) => {
    const orderedTypes = [...task.platformRaw].sort((a, b) => a.order - b.order).map((item) => item.account_type);
    return orderedTypes
        .map((type) => {
            const accountType = Number(type);
            return {
                type: accountType,
                // 与列表展示一致：先按 scene 匹配，再全局回退，避免默认任务 scene 未收录时变成「平台3」
                name: getPlatformName(task.scene as TaskScene, accountType),
                color: PLATFORM_COLOR_MAP[accountType] ?? "#CBD5E1",
            };
        })
        .filter((item) => !!item.type);
};

const getDemoDeviceName = (device: any) => device?.device_name || device?.name || device?.device_code || "未命名设备";

const getDemoDeviceStatus = (status: number | string | undefined) => {
    const value = Number(status);
    if (value === 0) return { label: "离线待机", color: "#FAAD14" };
    if (value === 2) return { label: "执行中", color: "#0065FB" };
    return { label: "在线运行中", color: "#52C41A" };
};

const selectDemoDevice = (device: any) => {
    selectedDemoDevice.value = device;
    demoParams.value.device_code = device.device_code;
};

const selectPlatform = (platform: any) => {
    selectedPlatform.value = platform;
    demoParams.value.account_type = platform.type;
};

const handleDemoTask = async (task: TaskItem) => {
    pendingDemoTask.value = task;
    if (demoContextPromise || demoDeviceList.value.length === 0) {
        uni.showLoading({ title: "加载设备中...", mask: true });
        await fetchDemoContext();
        uni.hideLoading();
    }

    if (demoDeviceList.value.length === 0) {
        uni.showToast({ title: "请先绑定设备后再演示", icon: "none", duration: 3000 });
        return;
    }

    if (demoDeviceList.value.length > 1) {
        selectDemoDevice(selectedDemoDevice.value ?? demoDeviceList.value[0]);
        showChooseDevice.value = true;
        return;
    }

    selectDemoDevice(demoDeviceList.value[0]);
    prepareDemoTask(task);
};

const prepareDemoTask = (task: TaskItem) => {
    const platforms = getTaskPlatformOptions(task);
    if (platforms.length === 0) {
        uni.showToast({ title: "任务平台异常，请重新配置", icon: "none", duration: 3000 });
        return;
    }

    const [startTime, endTime] = task.time.split("-");
    uni.showModal({
        title: "提示",
        content: "检测有任务在执行中，演示任务会中断当前任务，是否确定继续演示任务？",
        success: ({ confirm }) => {
            if (!confirm) return;
            demoParams.value = {
                device_code: selectedDemoDevice.value.device_code,
                source: task.scene,
                account_type: platforms[0].type,
                start_time: startTime,
                end_time: endTime,
                persona_type: demoPersonaType.value,
            };
            if (platforms.length > 1) {
                chooseAppPlatforms.value = platforms;
                selectPlatform(platforms[0]);
                showChooseApp.value = true;
            } else {
                handleCheckRealTask();
            }
        },
    });
};

const confirmDeviceSelection = () => {
    if (!selectedDemoDevice.value?.device_code) {
        uni.showToast({ title: "请选择演示设备", icon: "none", duration: 3000 });
        return;
    }
    showChooseDevice.value = false;
    if (pendingDemoTask.value) prepareDemoTask(pendingDemoTask.value);
};

const handleCheckRealTask = async () => {
    uni.showLoading({ title: "检查任务中...", mask: true });
    try {
        const res = await checkRealTask(demoParams.value);
        uni.hideLoading();
        if (res.is_demo_data == 1) {
            showConfirmDemoDialog.value = true;
            return;
        }
        startDemoTask();
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error || "检查失败，请重试", icon: "none", duration: 3000 });
    }
};

const confirmSelection = () => {
    showChooseApp.value = false;
    handleCheckRealTask();
};

const startDemoTask = async () => {
    uni.showLoading({ title: "创建中...", mask: true });
    try {
        await createDemoTask(demoParams.value);
        uni.hideLoading();
        uni.showToast({ title: "创建成功", icon: "none", duration: 3000 });
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({ title: error || "创建失败，请重试", icon: "none", duration: 3000 });
    }
};

const fetchDemoContext = async () => {
    if (demoContextPromise) return demoContextPromise;
    demoContextPromise = (async () => {
        const [detailResult, deviceResult] = await Promise.allSettled([
            getPersonDetail({ id: personId.value }),
            getPersonDeviceList({ persona_id: personId.value }),
        ]);
        if (detailResult.status === "fulfilled") {
            demoPersonaType.value = Number(detailResult.value?.persona_type || DEFAULT_PERSON_TYPE);
        }
        if (deviceResult.status === "fulfilled") {
            const deviceData = deviceResult.value;
            const devices = Array.isArray(deviceData) ? deviceData : deviceData?.devices ?? deviceData?.lists ?? [];
            demoDeviceList.value = devices.filter((item: any) => !!item?.device_code);
            selectedDemoDevice.value = demoDeviceList.value[0] ?? null;
        }
    })();
    try {
        await demoContextPromise;
    } finally {
        demoContextPromise = null;
    }
};

// ─── 弹窗相关 ──────────────────────────────────────────────────
const openAddTaskModal = async () => {
    if (!canEditTasks.value) {
        showReadonlyTaskToast();
        return;
    }
    // 每次打开添加弹窗都重新拉取，避免沿用编辑态残留，并同步后台最新开关
    uni.showLoading({ title: "加载中...", mask: true });
    try {
        await taskStore.fetchAddableScenes();
    } finally {
        uni.hideLoading();
    }
    editingTaskId.value = null;
    if (!resetNewTaskToFirstSelectable()) {
        uni.showToast({ title: "暂无可添加的任务类型", icon: "none", duration: 3000 });
        return;
    }
    newTask.startTime = DEFAULT_START_TIME;
    newTask.endTime = DEFAULT_END_TIME;
    addTaskError.value = "";
    showAddTaskModal.value = true;
};

const closeAddTaskModal = () => {
    suppressTaskEditUntil = Date.now() + 300;
    showAddTaskModal.value = false;
    editingTaskId.value = null;
};

const handleTaskTypeChange = (e: any) => {
    const idx = Number(e.detail.value);
    const selected = modalTaskConfigList.value[idx];
    if (!selected) return;
    taskTypeIndex.value = idx;
    newTask.scene = selected.scene;
    newTask.platforms = selected.platforms[0] ? [selected.platforms[0].name] : [];
    updateEndTime();
};

const togglePlatform = (name: string) => {
    const idx = newTask.platforms.indexOf(name);
    if (idx > -1) newTask.platforms.splice(idx, 1);
    else newTask.platforms.push(name);
    updateEndTime();
};

const handleStartTimeChange = (e: any) => {
    newTask.startTime = e.detail.value;
    updateEndTime();
};

const handleEndTimeChange = (e: any) => {
    newTask.endTime = e.detail.value;
};

const updateEndTime = () => {
    if (!newTask.startTime) return;
    const startMins = timeToMins(newTask.startTime);
    if (newTask.platforms.length > 1) {
        newTask.endTime = minsToTime(startMins + newTask.platforms.length * MINUTES_PER_PLATFORM);
    } else if (newTask.scene === SCENE_VIDEO_PUBLISH) {
        newTask.endTime = minsToTime(startMins + MINUTES_PER_PLATFORM);
    }
};

const handleSaveTask = async () => {
    addTaskError.value = "";

    if (!canEditTasks.value) {
        addTaskError.value = "该模板不可编辑，请克隆后再修改";
        return;
    }

    if (newTask.platforms.length === 0) {
        addTaskError.value = "请至少选择一个执行平台";
        return;
    }
    const startMins = timeToMins(newTask.startTime);
    const endMins = timeToMins(newTask.endTime);

    if (startMins < FORBIDDEN_ZONE_END_MINS) {
        addTaskError.value = "00:00-06:00 为固定创作时间，不可排期";
        return;
    }
    if (startMins >= endMins) {
        addTaskError.value = "结束时间必须晚于开始时间";
        return;
    }
    if (newTask.platforms.length === 1 && newTask.scene !== SCENE_VIDEO_PUBLISH && endMins - startMins < 5) {
        addTaskError.value = "该任务执行时间最少需要 5 分钟";
        return;
    }

    const hasConflict = displayTasks.value.some((task) => {
        if (editingTaskId.value !== null && task.id === editingTaskId.value) return false;
        const [tS, tE] = task.time.split("-");
        return startMins < timeToMins(tE) && endMins > timeToMins(tS);
    });
    if (hasConflict) {
        addTaskError.value = "时间段与现有任务冲突，请重新选择";
        return;
    }

    isSaving.value = true;
    try {
        const taskPayload = {
            time: `${newTask.startTime}-${newTask.endTime}`,
            scene: newTask.scene,
            platforms: [...newTask.platforms],
        };

        if (isPreviewEditableTemplate.value) {
            editingTaskId.value !== null
                ? await taskStore.updatePreviewTask(previewTemplateId.value, editingTaskId.value, taskPayload)
                : await taskStore.addPreviewTask(previewTemplateId.value, taskPayload);
        } else if (editingTaskId.value === null && !taskStore.currentTemplateId) {
            await taskStore.ensureEditableTemplate(DEFAULT_TEMPLATE_NAME);
            await taskStore.addTask(taskPayload);
        } else {
            editingTaskId.value !== null
                ? await taskStore.updateTask(editingTaskId.value, taskPayload)
                : await taskStore.addTask(taskPayload);
        }

        uni.showToast({
            title: editingTaskId.value !== null ? "修改成功" : "添加成功",
            icon: "none",
            duration: 3000,
        });
        closeAddTaskModal();
    } catch (error: any) {
        addTaskError.value = editingTaskId.value !== null ? error || "修改失败，请重试" : error || "添加失败，请重试";
    } finally {
        isSaving.value = false;
    }
};

const back = () => {
    // task_flow / task_flow_template 是 detail 之上的同一层「卡槽」，二者通过 redirect 互换；
    // 返回 detail 应直接出栈，避免 redirect 生成重复的 detail 实例导致返回无反应。
    const pages = getCurrentPages();
    if (pages.length > 1) {
        uni.navigateBack();
    } else {
        uni.redirectTo({
            url: `/ai_modules/person/pages/detail/detail?id=${personId.value}`,
        });
    }
};

// ─── 初始化 ────────────────────────────────────────────────────
const init = async () => {
    try {
        taskStore.personaId = Number(personId.value);
        fetchDemoContext();
        if (previewTemplateId.value) {
            await Promise.all([
                taskStore.fetchTemplateDetail(),
                taskStore.fetchTemplateById(previewTemplateId.value),
                taskStore.fetchAddableScenes(),
            ]);
        } else {
            await taskStore.init(Number(personId.value));
        }
    } finally {
        initialized.value = true;
    }
};

onLoad((options: any) => {
    personId.value = options?.id ?? "";
    previewTemplateId.value = options?.templateId ?? "";
    init();
});

onShow(() => {
    if (!initialized.value) return;
    fetchDemoContext();
    if (isPreviewMode.value) return;
    taskStore.fetchTemplateDetail();
});

onUnload(() => {
    if (isPreviewMode.value) taskStore.clearPreview();
});
</script>
