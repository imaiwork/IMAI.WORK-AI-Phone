<template>
    <view class="h-screen flex flex-col bg-[#F7F9FC]">
        <u-navbar
            title-bold
            :title="navTitle"
            :border-bottom="false"
            :is-fixed="false"
            :background="{ background: '#ffffff' }" />

        <view
            class="flex-shrink-0 bg-white border-[0] border-b border-solid border-[#F0F2F5] px-6 pt-[24rpx] pb-[20rpx]">
            <steps :steps="STEPS" :step="currentStep" @step="handleStepJump" />
        </view>

        <view class="grow min-h-0 mt-[16rpx]">
            <view v-show="currentStep === 1" class="flex flex-col h-full">
                <view class="flex items-center justify-between px-4 mb-[12rpx]">
                    <view class="flex items-center gap-[10rpx]">
                        <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                        <text class="text-[30rpx] font-extrabold text-[#0D1117]">
                            {{ taskType == TaskType.IMAGE ? "图组列表" : "视频素材" }}
                        </text>
                        <text class="text-xs text-[#9CA3AF]">（{{ formData.materialList.length }}）</text>
                    </view>
                    <view
                        v-if="taskType == TaskType.IMAGE"
                        class="flex items-center gap-[6rpx] bg-[#EBF2FF] px-[24rpx] py-[12rpx] rounded-full"
                        @click="handleEditMaterial()">
                        <u-icon name="plus" color="#0065fb" size="18" />
                        <text class="font-bold text-primary">添加图组</text>
                    </view>
                </view>

                <view class="grow min-h-0">
                    <template v-if="taskType == TaskType.IMAGE">
                        <scroll-view scroll-y class="h-full" v-if="formData.materialList.length > 0">
                            <view class="px-4 flex flex-col gap-[16rpx] pb-4">
                                <view
                                    v-for="(item, index) in formData.materialList"
                                    :key="index"
                                    class="bg-white rounded-[24rpx] p-[28rpx] shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                                    <view class="flex items-center justify-between mb-[16rpx]">
                                        <view class="flex items-center gap-[10rpx]">
                                            <view
                                                class="w-[40rpx] h-[40rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center">
                                                <text class="text-[22rpx] font-bold text-primary">
                                                    {{ index + 1 < 10 ? "0" + (index + 1) : index + 1 }}
                                                </text>
                                            </view>
                                            <text class="text-[28rpx] font-bold text-[#0D1117]">图组</text>
                                        </view>
                                        <view class="flex items-center gap-[8rpx]" @click="handleEditMaterial(index)">
                                            <text class="font-bold text-primary">{{ item.url.length }}</text>
                                            <text class="text-xs text-[#9CA3AF]">张</text>
                                            <u-icon name="arrow-right" size="20" color="#9CA3AF" />
                                        </view>
                                    </view>
                                    <view class="grid grid-cols-4 gap-[10rpx]">
                                        <view v-for="(image, iindex) in item.url" :key="iindex" class="aspect-[3/4]">
                                            <image
                                                :src="image"
                                                class="w-full h-full rounded-[12rpx]"
                                                mode="aspectFill" />
                                        </view>
                                    </view>
                                    <view
                                        class="flex items-center justify-between mt-[20rpx] pt-[16rpx] border-[0] border-t border-solid border-[#F0F2F5]">
                                        <view
                                            class="flex items-center gap-[8rpx]"
                                            @click.stop="handleDeleteMaterial(index)">
                                            <view
                                                class="w-[36rpx] h-[36rpx] rounded-full bg-[#FFF1F2] flex items-center justify-center">
                                                <u-icon name="trash" color="#F56C6C" size="16" />
                                            </view>
                                            <text class="text-xs text-[#F56C6C]">删除</text>
                                        </view>
                                        <view class="flex items-center gap-[6rpx]" @click="handleEditMaterial(index)">
                                            <u-icon name="edit-pen" color="#9CA3AF" size="16" />
                                            <text class="text-xs text-[#9CA3AF]">点击编辑</text>
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </scroll-view>

                        <view v-else class="flex flex-col items-center justify-center h-full px-8">
                            <view class="relative mb-[48rpx]">
                                <view
                                    class="w-[280rpx] h-[280rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center">
                                    <view
                                        class="w-[200rpx] h-[200rpx] rounded-full bg-[#DBEAFE] flex items-center justify-center">
                                        <view
                                            class="w-[120rpx] h-[120rpx] rounded-[32rpx] flex items-center justify-center shadow-[0_8rpx_24rpx_rgba(0,101,251,0.25)]"
                                            style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)">
                                            <u-icon name="photo" color="#fff" size="44" />
                                        </view>
                                    </view>
                                </view>
                                <view
                                    class="absolute top-[8rpx] right-[-12rpx] w-[56rpx] h-[56rpx] rounded-full bg-white shadow-[0_4rpx_12rpx_rgba(0,101,251,0.15)] flex items-center justify-center">
                                    <u-icon name="plus" color="#0065fb" size="22" />
                                </view>
                                <view
                                    class="absolute bottom-[8rpx] left-[-12rpx] w-[56rpx] h-[56rpx] rounded-full bg-[#FEF9C3] shadow-[0_4rpx_12rpx_rgba(0,0,0,0.08)] flex items-center justify-center">
                                    <u-icon name="grid" color="#D97706" size="22" />
                                </view>
                            </view>
                            <text class="text-[34rpx] font-extrabold text-[#0D1117] mb-[16rpx]">还没有图组</text>
                            <text class="text-[#9CA3AF] text-center leading-relaxed mb-[64rpx]">
                                点击下方按钮，添加您的第一个图组素材
                            </text>
                            <view
                                class="flex items-center gap-[10rpx] h-[96rpx] px-[64rpx] rounded-[24rpx] relative overflow-hidden shadow-[0_10rpx_30rpx_rgba(28,111,235,0.30)]"
                                style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                                @click="handleEditMaterial()">
                                <u-icon name="plus" size="24" color="#fff" />
                                <text class="text-[30rpx] font-extrabold text-white">添加图组</text>
                            </view>
                        </view>
                    </template>

                    <template v-else>
                        <scroll-view scroll-y class="h-full">
                            <view class="p-4 grid grid-cols-3 gap-[16rpx]">
                                <view
                                    v-for="(item, index) in formData.materialList"
                                    :key="index"
                                    class="relative rounded-[24rpx] overflow-hidden aspect-[3/4]">
                                    <image :src="item.url[0]" class="w-full h-full" mode="aspectFill" />
                                    <view
                                        class="absolute bottom-0 left-0 right-0 h-[100rpx]"
                                        style="background: linear-gradient(to top, rgba(0, 0, 0, 0.45), transparent)" />
                                    <view
                                        class="absolute inset-0 flex items-center justify-center"
                                        @click="handlePlayVideo(item.url)">
                                        <view
                                            class="w-[72rpx] h-[72rpx] rounded-full bg-[#fffff]/30 flex items-center justify-center border border-solid border-[#ffffff]/50">
                                            <u-icon name="play-right-fill" color="#fff" size="28" />
                                        </view>
                                    </view>
                                    <view
                                        class="absolute top-[12rpx] right-[12rpx] w-[40rpx] h-[40rpx] rounded-full bg-[#000000]/40 flex items-center justify-center"
                                        @click="handleDeleteVideo(index)">
                                        <u-icon name="close" size="14" color="#ffffff" />
                                    </view>
                                    <view class="absolute bottom-[12rpx] left-[12rpx]">
                                        <view
                                            class="px-[16rpx] py-[6rpx] text-white text-[22rpx] rounded-full border border-solid border-[#ffffff]/40 bg-[#000000]/30"
                                            @click="handleReplaceVideo(index)">
                                            替换
                                        </view>
                                    </view>
                                </view>
                                <view
                                    v-if="formData.materialList.length < VIDEO_CONFIG.limit"
                                    class="bg-white aspect-[3/4] rounded-[20rpx] border-2 border-dashed border-[#0065fb]/30 bg-[#F0F6FF] flex flex-col items-center justify-center gap-[10rpx]"
                                    @click="showUploadCategoryPanel = true">
                                    <view
                                        class="w-[56rpx] h-[56rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center">
                                        <u-icon name="plus" color="#0065fb" size="28" />
                                    </view>
                                    <text class="text-xs text-primary font-semibold">添加视频</text>
                                </view>
                            </view>
                        </scroll-view>
                    </template>
                </view>
            </view>

            <view v-show="currentStep === 2" class="flex flex-col h-full">
                <view class="px-4 flex items-center gap-[16rpx] mb-[16rpx]">
                    <navigator
                        url="/ai_modules/device/pages/task_copywriter/task_copywriter"
                        hover-class="none"
                        class="flex-1 flex items-center justify-center gap-[10rpx] h-[96rpx] rounded-[24rpx] bg-white border border-solid border-[#E5E9F0] shadow-[0_2rpx_8rpx_rgba(0,0,0,0.04)]"
                        @click="editCopywriterIndex = -1">
                        <u-icon name="edit-pen" color="#4B5563" size="22" />
                        <text class="text-[28rpx] font-bold text-[#334155]">手动输入</text>
                    </navigator>
                    <navigator
                        url="/ai_modules/device/pages/task_ai_copywriter/task_ai_copywriter"
                        hover-class="none"
                        class="flex-1 h-[96rpx] flex items-center justify-center gap-[10rpx] rounded-[24rpx] relative overflow-hidden shadow-[0_8rpx_20rpx_rgba(0,101,251,0.25)]"
                        style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                        @click="editCopywriterIndex = -1">
                        <image src="/static/images/common/magic_white.png" class="w-[32rpx] h-[32rpx]" />
                        <text class="text-[28rpx] font-bold text-white">AI 生成</text>
                    </navigator>
                </view>

                <view class="flex items-center gap-[10rpx] px-4 mb-[12rpx]">
                    <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                    <text class="text-[28rpx] font-extrabold text-[#0D1117]">文案列表</text>
                    <text class="text-xs text-[#9CA3AF]">（{{ formData.copywriterList.length }}）</text>
                </view>

                <view class="grow min-h-0">
                    <scroll-view scroll-y class="h-full" v-if="formData.copywriterList.length > 0">
                        <view class="px-4 flex flex-col gap-[16rpx] pb-4">
                            <view
                                v-for="(item, index) in formData.copywriterList"
                                :key="index"
                                class="bg-white rounded-[24rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]"
                                @click="handleEditCopywriter(index)">
                                <view class="flex">
                                    <view class="w-[6rpx] flex-shrink-0 bg-primary rounded-l-[24rpx]" />
                                    <view class="flex-1 px-[24rpx] pt-[20rpx] pb-[18rpx]">
                                        <view class="flex items-center justify-between mb-[12rpx]">
                                            <view class="flex items-center gap-[10rpx]">
                                                <view
                                                    class="w-[40rpx] h-[40rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center flex-shrink-0">
                                                    <text class="text-[22rpx] font-bold text-primary">{{
                                                        index + 1
                                                    }}</text>
                                                </view>
                                                <text class="text-[28rpx] font-bold text-[#0D1117] line-clamp-1">{{
                                                    item.title
                                                }}</text>
                                            </view>
                                            <view
                                                class="w-[44rpx] h-[44rpx] rounded-full bg-[#F3F4F6] flex items-center justify-center flex-shrink-0"
                                                @click.stop="handleDeleteCopywriter(index)">
                                                <u-icon name="close" color="#9CA3AF" size="16" />
                                            </view>
                                        </view>
                                        <text class="text-[#4B5563] leading-relaxed line-clamp-2">{{
                                            item.content
                                        }}</text>
                                        <view class="mt-[12rpx] flex flex-wrap gap-[8rpx]" v-if="item.topic.length > 0">
                                            <view
                                                v-for="(tag, tindex) in item.topic"
                                                :key="tindex"
                                                class="px-[12rpx] py-[4rpx] rounded-full bg-[#EBF2FF]">
                                                <text class="text-[22rpx] text-primary">{{ tag }}</text>
                                            </view>
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </scroll-view>

                    <copywriter-empty v-else />
                </view>
            </view>

            <view v-show="currentStep === 3" class="h-full">
                <scroll-view scroll-y class="h-full">
                    <view class="px-4 pb-[32rpx] flex flex-col gap-[16rpx]">
                        <view>
                            <view class="flex items-center gap-[10rpx] mb-[12rpx]">
                                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                <text class="text-[30rpx] font-extrabold text-[#0D1117]">基础设置</text>
                            </view>
                            <view
                                class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                                <view class="px-[28rpx] py-[22rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                    <text class="text-xs text-[#9CA3AF] font-semibold block mb-[12rpx]">任务名称</text>
                                    <view class="bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[14rpx]">
                                        <u-input
                                            v-model="formData.name"
                                            placeholder-style="font-size:26rpx;color:#C0C4CC;"
                                            placeholder="请输入任务名称"
                                            maxlength="30" />
                                    </view>
                                </view>
                                <navigator
                                    :url="`/ai_modules/device/pages/account_choose/account_choose?accounts=${JSON.stringify(
                                        formData.accounts,
                                    )}`"
                                    hover-class="none"
                                    class="flex items-center justify-between px-[28rpx] h-[100rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                    <text class="text-[28rpx] font-semibold text-[#0D1117]">发布账号</text>
                                    <view class="flex items-center gap-[6rpx]">
                                        <text
                                            class=""
                                            :class="
                                                formData.accounts.length ? 'text-primary font-bold' : 'text-[#C0C4CC]'
                                            ">
                                            {{
                                                formData.accounts.length
                                                    ? `${formData.accounts.length} 个账号`
                                                    : "选择账号"
                                            }}
                                        </text>
                                        <u-icon name="arrow-right" size="20" color="#C0C4CC" />
                                    </view>
                                </navigator>
                                <view
                                    class="flex items-center justify-between px-[28rpx] h-[100rpx] border-[0] border-b border-solid border-[#F0F2F5]"
                                    @click="handleKeywordsEdit">
                                    <text class="text-[28rpx] font-semibold text-[#0D1117]">标记地点</text>
                                    <view class="flex items-center gap-[6rpx]">
                                        <text
                                            class=""
                                            :class="formData.location ? 'text-primary font-bold' : 'text-[#C0C4CC]'">
                                            {{ formData.location || "选填" }}
                                        </text>
                                        <u-icon name="arrow-right" size="20" color="#C0C4CC" />
                                    </view>
                                </view>
                                <view class="px-[28rpx] py-[22rpx]">
                                    <text class="text-xs text-[#9CA3AF] font-semibold block mb-[16rpx]"
                                        >发布频率（每日）</text
                                    >
                                    <view class="flex flex-wrap gap-[12rpx]">
                                        <view
                                            v-for="item in PUBLISH_FREQUENCY_OPTIONS"
                                            :key="item"
                                            class="h-[68rpx] px-[28rpx] flex items-center justify-center rounded-[20rpx] transition-all duration-200"
                                            :class="
                                                formData.publish_frep === item && currentFrequencyIdx !== 5
                                                    ? 'bg-[#EBF2FF] shadow-[inset_0_0_0_1.5rpx_#BFDBFE]'
                                                    : 'bg-[#F0F2F5]'
                                            "
                                            @click="handlePublishFrequency(item)">
                                            <text
                                                class="font-bold"
                                                :class="
                                                    formData.publish_frep === item && currentFrequencyIdx !== 5
                                                        ? 'text-primary'
                                                        : 'text-[#9CA3AF]'
                                                ">
                                                {{ item }}条
                                            </text>
                                        </view>
                                        <view
                                            class="h-[68rpx] px-[28rpx] flex items-center justify-center rounded-[20rpx] transition-all duration-200"
                                            :class="
                                                currentFrequencyIdx == 5
                                                    ? 'bg-[#EBF2FF] shadow-[inset_0_0_0_1.5rpx_#BFDBFE]'
                                                    : 'bg-[#F0F2F5]'
                                            "
                                            @click="showNumberPop = true">
                                            <text
                                                class="font-bold"
                                                :class="currentFrequencyIdx == 5 ? 'text-primary' : 'text-[#9CA3AF]'">
                                                {{ customPublishFrep ? `${customPublishFrep}条` : "自定义" }}
                                            </text>
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </view>

                        <view>
                            <view class="flex items-center gap-[10rpx] mb-[12rpx]">
                                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                <text class="text-[30rpx] font-extrabold text-[#0D1117]">时间设置</text>
                            </view>
                            <view
                                class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                                <view
                                    class="px-[20rpx] pt-[20rpx] pb-[16rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                    <view class="flex bg-[#F0F2F5] rounded-[16rpx] p-[6rpx] gap-[8rpx]">
                                        <view
                                            v-for="(item, index) in TASK_EXEC_TYPE_OPTIONS"
                                            :key="index"
                                            class="flex-1 flex items-center justify-center gap-[8rpx] h-[72rpx] rounded-[12rpx] transition-all duration-200"
                                            :class="
                                                formData.task_exec_type === item.value
                                                    ? 'bg-white text-primary font-bold shadow-[0_2rpx_8rpx_rgba(0,0,0,0.08)]'
                                                    : 'text-[#9CA3AF]'
                                            "
                                            @click="formData.task_exec_type = item.value">
                                            <u-icon
                                                :name="item.icon"
                                                size="28"
                                                :color="
                                                    formData.task_exec_type === item.value ? '#0065fb' : '#9CA3AF'
                                                " />
                                            <text>{{ item.text }}</text>
                                        </view>
                                    </view>
                                </view>
                                <view class="px-[28rpx] py-[22rpx]">
                                    <text class="text-xs text-[#9CA3AF] font-semibold block mb-[16rpx]">任务频率</text>
                                    <view class="flex flex-wrap gap-[12rpx]">
                                        <view
                                            v-for="(item, index) in TASK_FREQUENCY_OPTIONS"
                                            :key="index"
                                            class="h-[68rpx] px-[28rpx] flex items-center justify-center rounded-[20rpx] transition-all duration-200"
                                            :class="
                                                formData.task_frep == item && currentDayFrequencyIdx != 5
                                                    ? 'bg-[#EBF2FF] shadow-[inset_0_0_0_1.5rpx_#BFDBFE]'
                                                    : 'bg-[#F0F2F5]'
                                            "
                                            @click="handleDayFrequency(item, index)">
                                            <text
                                                class="font-bold"
                                                :class="
                                                    formData.task_frep == item && currentDayFrequencyIdx != 5
                                                        ? 'text-primary'
                                                        : 'text-[#9CA3AF]'
                                                ">
                                                {{ item }}天
                                            </text>
                                        </view>
                                        <view
                                            class="h-[68rpx] px-[28rpx] flex items-center justify-center rounded-[20rpx] transition-all duration-200"
                                            :class="
                                                currentDayFrequencyIdx == 5
                                                    ? 'bg-[#EBF2FF] shadow-[inset_0_0_0_1.5rpx_#BFDBFE]'
                                                    : 'bg-[#F0F2F5]'
                                            "
                                            @click="handleCustomDate">
                                            <text
                                                class="font-bold"
                                                :class="
                                                    currentDayFrequencyIdx == 5 ? 'text-primary' : 'text-[#9CA3AF]'
                                                ">
                                                {{
                                                    formData.custom_date && currentDayFrequencyIdx == 5
                                                        ? "更改日期"
                                                        : "自定义"
                                                }}
                                            </text>
                                        </view>
                                    </view>
                                    <view
                                        class="mt-[20rpx]"
                                        v-if="formData.custom_date.length && currentDayFrequencyIdx == 5">
                                        <view class="flex items-center justify-between mb-[12rpx]">
                                            <text class="text-xs text-[#9CA3AF] font-semibold">任务时间</text>
                                            <view
                                                class="flex items-center gap-[4rpx]"
                                                v-if="formData.custom_date.length > 8"
                                                @click="isExpandDate = !isExpandDate">
                                                <text class="text-xs text-[#9CA3AF]">{{
                                                    isExpandDate ? "收起" : "展开"
                                                }}</text>
                                                <u-icon
                                                    :name="isExpandDate ? 'arrow-up' : 'arrow-down'"
                                                    size="22"
                                                    color="#9CA3AF" />
                                            </view>
                                        </view>
                                        <view
                                            :class="{
                                                'max-h-[120rpx] overflow-hidden': !isExpandDate,
                                            }">
                                            <view class="flex flex-wrap gap-[10rpx]">
                                                <view
                                                    v-for="(item, index) in formData.custom_date"
                                                    :key="index"
                                                    class="px-[16rpx] py-[8rpx] rounded-[12rpx] bg-[#EBF2FF]">
                                                    <text class="text-[22rpx] text-primary font-semibold">{{
                                                        formatDate(item)
                                                    }}</text>
                                                </view>
                                            </view>
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </view>

                        <view>
                            <view class="flex items-center gap-[10rpx] mb-[12rpx]">
                                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                <text class="text-[30rpx] font-extrabold text-[#0D1117]">发布时间</text>
                                <view
                                    class="flex items-center gap-[4rpx] bg-[#FFF7ED] px-[12rpx] py-[4rpx] rounded-full">
                                    <u-icon name="info-circle" color="#D97706" size="16" />
                                    <text class="text-[20rpx] text-[#D97706] font-semibold"
                                        >间隔须大于 {{ TIME_INTERVAL }} 分钟</text
                                    >
                                </view>
                            </view>
                            <view
                                v-for="(item, configIndex) in formData.time_config"
                                :key="configIndex"
                                class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)] mb-[12rpx]">
                                <view
                                    class="flex items-center gap-[10rpx] px-[28rpx] py-[18rpx] border-[0] border-b border-solid border-[#F0F2F5] bg-[#F8FAFC]">
                                    <view class="w-[8rpx] h-[8rpx] rounded-full bg-primary" />
                                    <text class="text-[28rpx] font-bold text-primary">{{ formatDate(item.date) }}</text>
                                </view>
                                <view class="px-[28rpx] py-[20rpx] flex flex-col gap-[20rpx]">
                                    <view v-for="(time, timeIndex) in item.times" :key="timeIndex">
                                        <view class="flex items-center gap-[8rpx] mb-[10rpx]">
                                            <view
                                                class="w-[32rpx] h-[32rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center flex-shrink-0">
                                                <text class="text-[18rpx] font-bold text-primary">{{
                                                    timeIndex + 1
                                                }}</text>
                                            </view>
                                            <text class="text-xs text-[#9CA3AF]"
                                                >第 {{ timeIndex + 1 }} 个内容任务</text
                                            >
                                        </view>
                                        <template v-if="isImmediateFirstSlot(configIndex, timeIndex)">
                                            <view
                                                class="flex items-center justify-between h-[80rpx] bg-[#F0FDF4] rounded-[16rpx] px-[20rpx] border border-solid border-[#BBF7D0]">
                                                <text class="font-semibold text-[#16A34A]">今日发布时间</text>
                                                <view
                                                    class="px-[20rpx] py-[8rpx] rounded-full bg-[#DCFCE7] border border-solid border-[#BBF7D0]">
                                                    <text class="text-xs font-bold text-[#16A34A]">立即执行</text>
                                                </view>
                                            </view>
                                        </template>
                                        <template v-else>
                                            <view class="flex items-center gap-[12rpx]">
                                                <view
                                                    class="flex-1 h-[80rpx] flex items-center justify-between px-[20rpx] rounded-[16rpx] border border-solid transition-all"
                                                    :class="
                                                        timeErrors[timeIndex]?.start_time
                                                            ? 'border-[#EF4444] bg-[#FEF2F2]'
                                                            : 'border-[#E5E9F0] bg-[#F7F9FC]'
                                                    ">
                                                    <picker
                                                        mode="time"
                                                        class="w-full"
                                                        :value="time.start_time"
                                                        @change="handleStartTimeChange($event, configIndex, timeIndex)">
                                                        <view class="flex items-center justify-between">
                                                            <text
                                                                class=""
                                                                :class="
                                                                    timeErrors[timeIndex]?.start_time
                                                                        ? 'text-[#EF4444] font-bold'
                                                                        : time.start_time
                                                                        ? 'text-[#0D1117] font-bold'
                                                                        : 'text-[#C0C4CC]'
                                                                ">
                                                                {{ time.start_time || "开始时间" }}
                                                            </text>
                                                            <u-icon name="arrow-right" size="20" color="#C0C4CC" />
                                                        </view>
                                                    </picker>
                                                </view>
                                                <text class="text-xs text-[#9CA3AF] flex-shrink-0">至</text>
                                                <view
                                                    class="flex-1 h-[80rpx] flex items-center justify-between px-[20rpx] rounded-[16rpx] border border-solid transition-all"
                                                    :class="
                                                        timeErrors[timeIndex]?.end_time
                                                            ? 'border-[#EF4444] bg-[#FEF2F2]'
                                                            : 'border-[#E5E9F0] bg-[#F7F9FC]'
                                                    ">
                                                    <picker
                                                        mode="time"
                                                        class="w-full"
                                                        :value="time.end_time"
                                                        :disabled="!time.start_time"
                                                        @click="handleEndTimeClick(time.start_time)"
                                                        @change="handleEndTimeChange($event, configIndex, timeIndex)">
                                                        <view class="flex items-center justify-between">
                                                            <text
                                                                class=""
                                                                :class="
                                                                    timeErrors[timeIndex]?.end_time
                                                                        ? 'text-[#EF4444] font-bold'
                                                                        : time.end_time
                                                                        ? 'text-[#0D1117] font-bold'
                                                                        : 'text-[#C0C4CC]'
                                                                ">
                                                                {{ time.end_time || "结束时间" }}
                                                            </text>
                                                            <u-icon name="arrow-right" size="20" color="#C0C4CC" />
                                                        </view>
                                                    </picker>
                                                </view>
                                            </view>
                                        </template>
                                    </view>
                                </view>
                                <view
                                    v-if="Object.keys(timeErrors).length > 0"
                                    class="mx-[28rpx] mb-[20rpx] flex items-center gap-[8rpx] bg-[#FEF2F2] rounded-[12rpx] px-[16rpx] py-[12rpx] border border-solid border-[#FECACA]">
                                    <u-icon name="info-circle" color="#EF4444" size="18" />
                                    <text class="text-xs text-[#EF4444]">时间配置存在冲突</text>
                                </view>
                            </view>
                        </view>

                        <view
                            v-if="taskErrorMsg"
                            class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <view
                                class="flex items-center gap-[10rpx] px-[28rpx] py-[18rpx] border-b border-solid border-[#F0F2F5]">
                                <view class="w-[6rpx] h-[32rpx] bg-[#EF4444] rounded-full" />
                                <text class="text-[28rpx] font-bold text-[#EF4444]">任务冲突</text>
                            </view>
                            <view class="px-[28rpx] py-[20rpx]">
                                <text class="text-[#EF4444] leading-relaxed">{{ taskErrorMsg }}</text>
                            </view>
                        </view>
                    </view>
                </scroll-view>
            </view>
        </view>

        <view
            class="flex-shrink-0 bg-white border-[0] border-t border-solid border-[#F0F2F5] px-6 pt-[20rpx] pb-[40rpx] flex items-center gap-[16rpx]">
            <view
                v-if="currentStep != 1"
                class="h-[96rpx] px-[40rpx] rounded-[24rpx] flex items-center gap-[8rpx] border border-solid border-[#E5E9F0] bg-white"
                @click="navigateStep('prev')">
                <text class="text-[28rpx] font-semibold text-[#4B5563]">上一步</text>
            </view>
            <template v-if="currentStep != STEPS.length">
                <view
                    v-if="currentStep === 1"
                    class="w-[100rpx] h-[96rpx] rounded-[20rpx] flex flex-col items-center justify-center transition-all duration-300"
                    :class="formData.materialList.length > 0 ? 'bg-[#EBF2FF]' : 'bg-[#F0F2F5]'">
                    <text
                        class="text-[32rpx] font-extrabold leading-none"
                        :class="formData.materialList.length > 0 ? 'text-primary' : 'text-[#C0C4CC]'">
                        {{ formData.materialList.length }}
                    </text>
                    <text
                        class="text-[20rpx] mt-[4rpx]"
                        :class="formData.materialList.length > 0 ? 'text-[#0065fb]/70' : 'text-[#C0C4CC]'"
                        >已选</text
                    >
                </view>
                <view
                    class="flex-1 h-[96rpx] rounded-[24rpx] flex items-center justify-center gap-[8rpx] transition-all duration-300"
                    :class="canProceedNext ? 'shadow-[0_8rpx_24rpx_rgba(28,111,235,0.30)]' : ''"
                    :style="
                        canProceedNext
                            ? 'background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)'
                            : 'background: #C0C4CC'
                    "
                    @click="navigateStep('next')">
                    <text class="text-[30rpx] font-bold text-white">下一步</text>
                </view>
            </template>
            <template v-else>
                <view
                    class="flex-1 h-[96rpx] rounded-[24rpx] flex items-center justify-center gap-[10rpx] relative overflow-hidden shadow-[0_10rpx_30rpx_rgba(28,111,235,0.35)]"
                    style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                    @click="handleCreateTask">
                    <text class="text-[32rpx] font-extrabold text-white tracking-wide">创建任务</text>
                </view>
            </template>
        </view>
    </view>

    <upload-category-panel
        v-model="showUploadCategoryPanel"
        :show-categories="[UploadAlbumTypeEnum.Video, UploadCategoryEnum.Library, UploadCategoryEnum.Creation]"
        @select="handleSelectCategory" />
    <choose-material
        v-model="showVideoMaterial"
        type="video"
        :disable-group-select="true"
        :limit="replaceVideoIndex == -1 ? VIDEO_CONFIG.limit - formData.materialList.length : 1"
        @select="handleSelectVideoMaterial" />
    <confirm-dialog
        v-if="confirmDialogVisible"
        v-model="confirmDialogVisible"
        confirm-text="删除"
        center
        content="是否确定删除图组？"
        @confirm="handleDeleteMaterialConfirm" />
    <confirm-dialog
        v-if="showCreateTaskSuccessDialog"
        v-model="showCreateTaskSuccessDialog"
        confirm-text="确定"
        center
        content="创建成功，回到任务列表？"
        :show-close="false"
        @confirm="handleCreateTaskSuccess" />
    <confirm-dialog
        v-if="showVideoUploadTip"
        v-model="showVideoUploadTip"
        confirm-text="去上传"
        :content="getVideoTipsContent"
        @close="
            isVideoInitialOpen = false;
            showVideoUploadTip = false;
        "
        @confirm="uploadAndProcessFiles('video')" />
    <upload-progress v-model="showUploadProgress" :upload-list="uploadMaterialList" />
    <video-preview
        v-model="showVideoPreview"
        :video-url="playItem.url"
        :poster="playItem.pic"
        @update:show="showVideoPreview = false" />
    <choose-history
        v-model="showHistory"
        type="video"
        :limit="replaceVideoIndex == -1 ? VIDEO_CONFIG.limit - formData.materialList.length : 1"
        @select="handleSelectHistory" />
    <number-pop
        v-model="showNumberPop"
        :max="99"
        :number="formData.publish_frep"
        title="发布频率"
        placeholder="请输入发布频率"
        confirmText="确定"
        @confirm="handleNumberPopConfirm" />
    <keywords-edit
        ref="keywordsEditRef"
        v-model="showKeywordsEdit"
        title="标记地点"
        @confirm="handleKeywordsEditConfirm" />
    <task-conflict-dialog
        v-if="showTaskMsgPop"
        v-model="showTaskMsgPop"
        :messages="taskMsgPopContent.messages"
        :errors="taskMsgPopContent.errors"
        @close="showTaskMsgPop = false"
        @confirm="handleTaskMsgPopConfirm" />
</template>

<script setup lang="ts">
import { getVideoCreationRecord } from "@/api/app";
import { getHotWriteDetail } from "@/api/hot_write";
import { getHotspotTaskDetail } from "@/api/hotspot";
import { getPuzzleTaskResultList } from "@/api/drawing";
import { ListenerTypeEnum } from "@/ai_modules/device/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import { isJson } from "@/utils/util";
import { UploadCategoryEnum, UploadAlbumTypeEnum } from "@/enums/appEnums";

import Steps from "@/ai_modules/device/components/steps/steps.vue";
import VideoPreview from "@/components/video-preview/video-preview.vue";
import NumberPop from "@/ai_modules/device/components/number-pop/number-pop.vue";
import KeywordsEdit from "@/ai_modules/device/components/keywords-edit/keywords-edit.vue";
import TaskConflictDialog from "@/ai_modules/device/components/task-conflict-dialog/task-conflict-dialog.vue";
import CopywriterEmpty from "@/ai_modules/device/components/copywriter-empty/copywriter-empty.vue";

// ─── 引入各步骤 Hooks ──────────────────────────────────────────────────────────
import { useStep } from "./hooks/useStep";
import { useMaterialStep } from "./hooks/useMaterialStep";
import { useCopywriterStep } from "./hooks/useCopywriter";
import { useCreateTask } from "./hooks/useCreateTask";

// ─── 引入类型 & 常量 ───────────────────────────────────────────────────────────
import {
    TaskType,
    VIDEO_CONFIG,
    STEPS,
    PUBLISH_FREQUENCY_OPTIONS,
    TASK_FREQUENCY_OPTIONS,
    TASK_EXEC_TYPE_OPTIONS,
    TIME_INTERVAL,
} from "./hooks/types";
import type { FormData } from "./hooks/types";

// ─── 共享表单数据（页面层统一维护，传入各 Hook） ──────────────────────────────
const taskType = ref<TaskType>(TaskType.VIDEO);

const formData = reactive<FormData>({
    name: "",
    introduction: "",
    copywriterList: [],
    materialList: [],
    time_config: [],
    accounts: [],
    publish_frep: 2,
    custom_date: [],
    task_frep: 1,
    location: "",
    task_exec_type: 1,
});

const navTitle = computed(() => (taskType.value == TaskType.IMAGE ? "发布图文" : "发布视频"));

const getVideoTipsContent = computed(
    () =>
        `<div>· 视频素材支持：${VIDEO_CONFIG.format.join("、")}格式，${VIDEO_CONFIG.size}M以内</div>
     <div class="mt-2">· 最多可传${VIDEO_CONFIG.limit}个视频</div>
     <div class="mt-2">· 不符合条件的视频会被自动删除</div>`,
);

// ─── Hook：步骤导航 ────────────────────────────────────────────────────────────
const { currentStep, canProceedNext, navigateStep, handleStepJump } = useStep(formData);

// ─── Hook：Step1 素材管理 ──────────────────────────────────────────────────────
const {
    showUploadCategoryPanel,
    showVideoMaterial,
    showHistory,
    showUploadProgress,
    showVideoPreview,
    showVideoUploadTip,
    isVideoInitialOpen,
    confirmDialogVisible,
    replaceVideoIndex,
    uploadMaterialList,
    playItem,
    handleSelectCategory,
    handleSelectVideoMaterial,
    handleSelectHistory,
    handleEditMaterial,
    handleDeleteMaterial,
    handleDeleteMaterialConfirm,
    handlePlayVideo,
    handleDeleteVideo,
    handleReplaceVideo,
    uploadAndProcessFiles,
    applyImgGroupResult,
    removeImgGroupIfEditing,
} = useMaterialStep(formData, taskType);

// ─── Hook：Step2 文案管理 ──────────────────────────────────────────────────────
const { editCopywriterIndex, handleEditCopywriter, handleDeleteCopywriter, onCopywriterConfirm } =
    useCopywriterStep(formData);

// ─── Hook：Step3 时间配置 + 创建任务 ──────────────────────────────────────────
const {
    currentFrequencyIdx,
    currentDayFrequencyIdx,
    customPublishFrep,
    isExpandDate,
    showNumberPop,
    showKeywordsEdit,
    showCreateTaskSuccessDialog,
    showTaskMsgPop,
    taskMsgPopContent,
    taskErrorMsg,
    timeErrors,
    keywordsEditRef,
    formatDate,
    isImmediateFirstSlot,
    changeTimeConfig,
    handlePublishFrequency,
    handleDayFrequency,
    handleCustomDate,
    handleNumberPopConfirm,
    handleKeywordsEdit,
    handleKeywordsEditConfirm,
    handleStartTimeChange,
    handleEndTimeChange,
    handleEndTimeClick,
    handleCreateTask,
    handleTaskMsgPopConfirm,
    handleCreateTaskSuccess,
} = useCreateTask(formData, taskType);

// ─── EventBus：统一分发所有子页面回调 ─────────────────────────────────────────
const { on } = useEventBusManager();

onLoad(async (options: any) => {
    // 初始化类型与默认名称
    if (options?.type) {
        taskType.value = Number(options.type) as TaskType;
        formData.name = `${taskType.value == TaskType.IMAGE ? "图文" : "视频"}矩阵任务${uni.$u.timeFormat(
            new Date(),
            "yyyymmddhhMM",
        )}`;
    }

    // 来源：视频创作
    if (options?.source === "creation_video") {
        const videoIds = JSON.parse(options.ids || "[]");
        const { lists } = await getVideoCreationRecord({ page_size: 99999 });
        lists
            ?.filter((item: any) => videoIds.includes(item.task_id))
            .forEach((item: any) => {
                formData.materialList.push({
                    url: [item.pic, item.clip_result_url || item.video_result_url],
                });
            });
    }
    // 来源：拼图
    else if (options?.source === "puzzle") {
        const { id, count } = options;
        const { lists } = await getPuzzleTaskResultList({ puzzle_setting_id: id, page_size: 999 });
        if (lists?.length) {
            const allImages = lists.flatMap((curr: any) => curr.puzzle_url);
            formData.materialList = createRandomImageGroups(allImages, Number(count));
        }
    }
    // 来源：爆款复刻（视频 / 图文按 media_type 分流）
    else if (options?.source === "hot_write") {
        const data = JSON.parse(options.data);
        const detail = await getHotWriteDetail({ id: data.id });
        const isImageText = Number(detail?.media_type) === TaskType.IMAGE;
        // 以详情 media_type 为准，避免图文误进「发布视频」
        taskType.value = isImageText ? TaskType.IMAGE : TaskType.VIDEO;
        formData.name = `${isImageText ? "图文" : "视频"}矩阵任务${uni.$u.timeFormat(new Date(), "yyyymmddhhMM")}`;
        if (isImageText) {
            const images = (
                Array.isArray(detail?.rewritten_images) && detail.rewritten_images.length
                    ? detail.rewritten_images
                    : Array.isArray(detail?.original_images)
                    ? detail.original_images
                    : []
            ).filter(Boolean);
            if (images.length) {
                formData.materialList.push({ url: images });
            }
        } else {
            formData.materialList.push({ url: [detail.thumbnail, detail.video_url] });
        }
        formData.copywriterList.push({
            is_title_show: 1,
            title: detail.title,
            content: detail.publish_text || detail.rewritten_text || "",
            topic: isJson(detail.publish_topic) ? JSON.parse(detail.publish_topic) : [],
        });
    }
    // 来源：热点追踪（成片 + 标题/口播文案/话题标签）
    else if (options?.source === "hotspot") {
        const data = JSON.parse(options.data);
        const detail = await getHotspotTaskDetail({ id: data.id });
        if (detail?.video_url) {
            formData.materialList.push({ url: [detail.pic || "", detail.video_url] });
        }
        formData.copywriterList.push({
            is_title_show: 1,
            title: detail?.publish_title || "",
            content: detail?.publish_content || "",
            topic: Array.isArray(detail?.options?.hashtags) ? detail.options.hashtags : [detail.topic],
        });
    }

    // EventBus 监听
    on("confirm", (res: any) => {
        const { type, data } = res;

        if (!data || data.length === 0) {
            if (type === ListenerTypeEnum.CHOOSE_IMG) removeImgGroupIfEditing();
            if (type === ListenerTypeEnum.CHOOSE_DATE) {
                currentDayFrequencyIdx.value = 0;
                formData.custom_date = [];
                changeTimeConfig();
            }
            return;
        }

        switch (type) {
            case ListenerTypeEnum.CHOOSE_IMG:
                applyImgGroupResult(data);
                break;
            case ListenerTypeEnum.TASK_COPYWRITER:
            case ListenerTypeEnum.TASK_AI_COPYWRITER:
                onCopywriterConfirm(data);
                break;
            case ListenerTypeEnum.CHOOSE_ACCOUNT:
                formData.accounts = data.map((item: any) => ({
                    account: item.account,
                    type: item.type,
                    id: item.id,
                }));
                break;
            case ListenerTypeEnum.CHOOSE_DATE:
                formData.custom_date = data;
                currentDayFrequencyIdx.value = 5;
                changeTimeConfig();
                break;
        }
    });

    // 初始化时间配置
    changeTimeConfig();
});

// ─── 工具函数（仅 onLoad 内使用） ─────────────────────────────────────────────
function selectRandomElements<T>(arr: T[], count: number): T[] {
    const result: T[] = [];
    const copy = [...arr];
    for (let i = 0; i < count; i++) {
        if (!copy.length) break;
        result.push(copy[Math.floor(Math.random() * copy.length)]);
    }
    return result;
}

function createRandomImageGroups(allImages: string[], numberOfGroups: number) {
    if (numberOfGroups <= 0) return [];
    return Array.from({ length: numberOfGroups }, () => {
        const count = Math.floor(Math.random() * Math.min(allImages.length, 9)) + 1;
        return { url: selectRandomElements(allImages, count) };
    });
}
</script>

<style lang="scss" scoped>
.navigator-wrap {
    @apply w-full h-full;
}
</style>
