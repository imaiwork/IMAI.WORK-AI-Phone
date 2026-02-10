<template>
    <view class="h-screen device-bg flex flex-col">
        <u-navbar
            title-bold
            :title="isCollect ? '留痕获客' : '截流获客'"
            :border-bottom="false"
            :is-fixed="false"
            :background="{
                background: 'transparent',
            }">
        </u-navbar>
        <view class="flex-shrink-0 h-[150rpx] flex items-center">
            <view class="grid grid-cols-4 w-full px-4">
                <view
                    v-for="item in steps"
                    :key="item.step"
                    class="common-step-item"
                    :class="{ active: step == item.step }"
                    @click="handleStep(item.step)">
                    <view v-if="step > item.step" class="common-step-item-success-icon">
                        <u-icon name="checkmark" color="#ffffff" size="14"></u-icon>
                    </view>
                    <view class="common-step-item-icon" v-else> </view>
                    <text class="common-step-item-title">{{ item.title }}</text>
                    <view
                        v-if="item.step !== steps.length"
                        class="common-step-item-line"
                        :class="{ '!border-primary': step > item.step }"></view>
                </view>
            </view>
        </view>
        <view class="grow min-h-0 mt-[24rpx]">
            <scroll-view class="h-full" scroll-y v-show="step === 1">
                <view class="px-4 pb-[100rpx]">
                    <view class="px-[40rpx] py-[30rpx] bg-white rounded-[20rpx]">
                        <view class="font-medium text-[30rpx]">获客方式</view>
                        <view class="mt-[20rpx]">
                            <u-radio-group v-model="formData.customer_type">
                                <view
                                    v-for="item in [
                                        { value: 0, label: '自由获客' },
                                        { value: 1, label: '同城获客' },
                                    ]"
                                    :key="item.value">
                                    <u-radio label-size="26" :size="28" :name="item.value">
                                        {{ item.label }}
                                    </u-radio>
                                    <view
                                        class="inline-block mt-[6rpx]"
                                        v-if="item.value === 1"
                                        @click.stop="showTemp = true">
                                        <u-icon name="question-circle-fill" color="#CCCCCC" size="24"></u-icon>
                                    </view>
                                </view>
                            </u-radio-group>
                        </view>
                        <view class="mt-[36rpx]" v-if="formData.customer_type === 0">
                            <view class="font-medium">目标地区</view>
                            <view
                                class="mt-[16rpx] h-[90rpx] flex items-center justify-between gap-x-2 px-[26rpx] bg-[#F3F3F3] rounded-[10rpx]"
                                @click="showRegionFormPopup = true">
                                <view class="font-medium" :class="{ 'text-primary': formData.region }">
                                    {{ formData.region || "无限制" }}
                                </view>
                                <view class="flex items-center gap-x-1 text-primary font-medium">
                                    修改
                                    <u-icon name="arrow-right" color="#0065fb" size="24"></u-icon>
                                </view>
                            </view>
                        </view>
                    </view>
                    <view class="mt-[30rpx]" v-if="formData.customer_type === 0">
                        <view class="font-medium text-[30rpx]"> 获客线索词 </view>
                        <view class="mt-[20rpx] bg-white rounded-[20rpx] px-[40rpx] py-[30rpx]">
                            <view class="flex items-center gap-x-2">
                                <view class="flex-1 bg-[#F3F3F3] rounded-[10rpx] h-[80rpx] flex items-center px-4">
                                    <u-input
                                        class="w-full"
                                        v-model="industryInput"
                                        placeholder="请输入，如：服装设计、女装"
                                        maxlength="100"
                                        clearable
                                        placeholder-style="font-size: 26rpx;" />
                                </view>
                                <view
                                    class="w-[160rpx] h-[80rpx] flex items-center justify-center bg-black rounded-[10rpx] text-white font-medium"
                                    @click="handleAddIndustry"
                                    >添加</view
                                >
                            </view>
                            <view class="mt-[30rpx] bg-[#F3F3F3] rounded-[16rpx]">
                                <scroll-view
                                    class="max-h-[300rpx]"
                                    ref="industryScrollViewRef"
                                    scroll-y
                                    :scroll-into-view="scrollToIndustryId"
                                    scroll-with-animation="true"
                                    v-if="formData.industry.length > 0">
                                    <view class="flex flex-wrap gap-2 p-[24rpx]">
                                        <view
                                            v-for="(item, index) in formData.industry"
                                            :key="index"
                                            :id="'industry_' + index"
                                            class="bg-white rounded-[50rpx] px-[24rpx] relative py-[10rpx] text-xs"
                                            @click="handleEditClue(index)">
                                            {{ item }}
                                            <view
                                                class="absolute right-[-10rpx] top-[-10rpx] w-[32rpx] h-[32rpx] flex items-center justify-center rounded-full bg-[#0000004d]"
                                                @click.stop="handleDeleteClue(index)">
                                                <u-icon name="close" color="#ffffff" size="16"></u-icon>
                                            </view>
                                        </view>
                                    </view>
                                </scroll-view>
                                <view v-else class="px-[24rpx] py-[50rpx] text-[#0000004d] text-center">
                                    输入或AI生成获客行业
                                </view>
                                <view class="p-[24rpx]">
                                    <view
                                        class="w-[160rpx] h-[64rpx] flex items-center justify-center gap-1 bg-white rounded-[10rpx]"
                                        @click="showClueGenPopup = true">
                                        <image
                                            src="@/ai_modules/device/static/icons/gen.svg"
                                            class="w-[24rpx] h-[24rpx]"></image>
                                        <text class="font-medium text-primary">AI生成 </text>
                                    </view>
                                </view>
                            </view>
                            <view class="mt-[30rpx]" v-if="historyIndustry.length > 0">
                                <view class="flex items-center justify-between">
                                    <view class="font-medium">历史记录</view>
                                    <view class="font-medium" @click="showHistoryIndustryPopup = true">
                                        更多
                                        <u-icon name="arrow-right" color="#B2B2B2" size="24"></u-icon>
                                    </view>
                                </view>
                                <view class="mt-[20rpx]">
                                    <view class="flex flex-wrap gap-[20rpx]">
                                        <view
                                            v-for="(item, index) in historyIndustry.slice(0, 5)"
                                            :key="index"
                                            class="rounded-full px-[24rpx] relative py-[10rpx] shadow-[0_0_0_2rpx_#0000001a]"
                                            @click="handleSelectHistoryIndustry(item.keyword)">
                                            {{ item.keyword }}
                                            <view
                                                class="absolute right-[-14rpx] top-[-14rpx] w-[32rpx] h-[32rpx] flex items-center justify-center rounded-full bg-[#0000004d]"
                                                @click.stop="handleDeleteHistoryIndustry(index)">
                                                <u-icon name="close" color="#ffffff" size="16"></u-icon>
                                            </view>
                                        </view>
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                    <view class="mt-[50rpx]" v-if="formData.customer_type === 0">
                        <view class="text-[30rpx] font-medium">每条线索词浏览作品的数量</view>
                        <view class="mt-[28rpx]">
                            <view class="flex flex-wrap gap-[20rpx]">
                                <view
                                    v-for="(item, index) in industryNumList"
                                    class="px-[34rpx] py-[18rpx] flex items-center justify-center bg-white text-[26rpx] relative rounded-[16rpx] font-medium text-[#00000080]"
                                    :key="index"
                                    :class="{
                                        'text-primary shadow-[0_0_0_2rpx_#0065FB]':
                                            formData.industryNum === item &&
                                            industryNumState.currentIndex !== industryNumList.length,
                                    }"
                                    @click="industryNumState.handleSelect(item, index)">
                                    {{ item }}条
                                </view>
                                <view
                                    class="px-[34rpx] py-[18rpx] flex items-center justify-center bg-white text-[26rpx] relative rounded-[16rpx] font-medium text-[#00000080]"
                                    :class="{
                                        'text-primary shadow-[0_0_0_2rpx_#0065FB]':
                                            industryNumState.currentIndex === industryNumList.length,
                                    }"
                                    @click="industryNumState.openCustom">
                                    {{
                                        industryNumState.currentIndex === industryNumList.length
                                            ? `${formData.industryNum}条`
                                            : industryNumState.savedCustomValue
                                            ? `${industryNumState.savedCustomValue}条`
                                            : "自定义"
                                    }}
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>
            <scroll-view class="h-full" scroll-y v-show="step === 2">
                <view class="px-4 pb-[100rpx]">
                    <view v-if="formData.customer_type === 0">
                        <view class="font-medium text-[30rpx]"> 评论词筛选 </view>
                        <view
                            class="mt-[36rpx] bg-white rounded-[20rpx] px-[40rpx] py-[24rpx]"
                            v-if="formData.comment_filter_list.length > 0">
                            <view class="font-medium">包含以下关键词</view>
                            <view class="flex flex-wrap gap-2 mt-[24rpx]">
                                <view
                                    v-for="(item, index) in formData.comment_filter_list"
                                    :key="index"
                                    class="border border-solid border-[#E5E5E5] rounded-[20rpx] px-2 py-[12rpx] flex items-center gap-x-2 break-all"
                                    @click="handleCommentFilterEdit(index)">
                                    {{ item.value }}
                                    <view
                                        class="flex-shrink-0 rounded-full flex item-center justify-center w-4 h-4 bg-[#0000004C]"
                                        @click.stop="handleCommentFilterDelete(index)">
                                        <u-icon name="close" color="#ffffff" size="16"></u-icon>
                                    </view>
                                </view>
                                <view
                                    class="border border-solid border-[#0065FB] rounded-[12rpx] px-[28rpx] py-[12rpx] flex items-center justify-center gap-x-1"
                                    @click="openCommentFilterEdit">
                                    <u-icon name="plus" color="#0065FB" size="20"></u-icon>
                                    <text class="text-primary font-medium">编辑</text>
                                </view>
                            </view>
                        </view>
                        <view v-else class="mt-10">
                            <view
                                class="border border-solid rounded-[20rpx] w-fit px-4 h-[88rpx] flex items-center justify-center mx-auto"
                                @click="openCommentFilterEdit">
                                <u-icon name="plus" size="20"></u-icon>
                                <text class="font-medium ml-1">添加评论词筛选</text>
                            </view>
                        </view>
                    </view>
                    <view class="mt-[50rpx]" v-if="!isCollect">
                        <view class="font-medium text-[30rpx]"> 触达方式和话术 </view>
                        <view class="mt-[18rpx] rounded-[20rpx] bg-white px-[30rpx] py-[28rpx]">
                            <view class="bg-[#F3F4FB] rounded-[16rpx] px-[4rpx] w-fit">
                                <view class="w-[360rpx] grid grid-cols-2 relative h-[72rpx]">
                                    <view
                                        v-for="(item, index) in ['发送评论', '发送私信']"
                                        :key="index"
                                        class="rounded-[12rpx] font-medium flex items-center justify-center z-10 transition-colors duration-500"
                                        :class="{ 'text-primary': commentIndex === index }"
                                        @click="commentIndex = index">
                                        {{ item }}
                                    </view>
                                    <view
                                        class="tab-slider"
                                        :style="{
                                            transform: `translateX(${commentIndex * 100}%)`,
                                        }"></view>
                                </view>
                            </view>

                            <view class="flex flex-wrap gap-2 mt-[28rpx]">
                                <view
                                    v-for="(item, index) in formData.comment_content_list"
                                    :key="index"
                                    class="border border-solid border-[#E5E5E5] rounded-[20rpx] px-2 h-[60rpx] flex items-center gap-x-2 break-all"
                                    @click="handleEditCommentContent(index)">
                                    {{ item }}
                                    <view
                                        class="flex-shrink-0 rounded-full flex item-center justify-center w-4 h-4 bg-[#0000004C]"
                                        @click.stop="handleCommentContentDelete(index)">
                                        <u-icon name="close" color="#ffffff" size="16"></u-icon>
                                    </view>
                                </view>
                                <view
                                    class="border border-solid border-[#0065FB] rounded-[12rpx] px-[28rpx] h-[60rpx] flex items-center justify-center"
                                    @click="handleEditCommentContent(-1)">
                                    <u-icon name="plus" color="#0065FB" size="20"></u-icon>
                                    <text class="text-primary font-medium ml-1">添加</text>
                                </view>
                            </view>
                        </view>
                    </view>
                    <view class="mt-[50rpx]" v-if="isCollect">
                        <view class="font-medium text-[30rpx]"> 留痕方式 </view>
                        <view class="mt-[18rpx] rounded-[20rpx] bg-white px-[30rpx] py-[28rpx]">
                            <view>
                                <u-checkbox-group class="w-full">
                                    <view class="flex flex-wrap gap-x-5 gap-y-2">
                                        <u-checkbox
                                            v-model="item.checked"
                                            v-for="(item, index) in getTouchTypeList"
                                            :key="index"
                                            :name="item.name"
                                            :size="28">
                                            <text class="text-base">{{ item.label }}</text>
                                        </u-checkbox>
                                    </view>
                                </u-checkbox-group>
                            </view>
                            <template
                                v-if="
                                    touchTypeList
                                        .filter((item) => item.checked)
                                        .map((item) => item.name)
                                        .includes(4)
                                ">
                                <view class="h-[1rpx] bg-[#F2F2F2] my-[28rpx]"></view>
                                <view class="font-medium text-primary mb-[30rpx]">评论作品方式</view>
                                <u-radio-group v-model="formData.comment_type" class="w-full">
                                    <view class="flex justify-between w-full gap-x-5">
                                        <u-radio
                                            v-for="(item, index) in [{ value: 1, label: '固定话术' }]"
                                            :key="index"
                                            :name="item.value"
                                            :size="28">
                                            <text class="text-base">{{ item.label }}</text>
                                        </u-radio>
                                    </view>
                                </u-radio-group>
                            </template>
                        </view>
                    </view>
                    <view class="mt-[50rpx]" v-if="isCollect ? formData.customer_type === 0 : true">
                        <view class="text-[30rpx] font-medium">{{ isCollect ? "留痕用户" : "触达" }}数量上限</view>
                        <view class="mt-[28rpx]">
                            <view class="flex flex-wrap gap-[20rpx]">
                                <view
                                    v-for="(item, index) in commentNumList"
                                    class="px-[34rpx] py-[18rpx] flex items-center justify-center bg-white text-[26rpx] relative rounded-[16rpx] font-medium text-[#00000080]"
                                    :key="index"
                                    :class="{
                                        'text-primary shadow-[0_0_0_2rpx_#0065FB]':
                                            formData.commentNum === item &&
                                            commentNumState.currentIndex !== commentNumList.length,
                                    }"
                                    @click="commentNumState.handleSelect(item, index)">
                                    {{ item }}条
                                </view>
                                <view
                                    class="px-[34rpx] py-[18rpx] flex items-center justify-center bg-white text-[26rpx] relative rounded-[16rpx] font-medium text-[#00000080]"
                                    :class="{
                                        'text-primary shadow-[0_0_0_2rpx_#0065FB]':
                                            commentNumState.currentIndex === commentNumList.length,
                                    }"
                                    @click="commentNumState.openCustom">
                                    {{
                                        commentNumState.currentIndex === commentNumList.length
                                            ? `${formData.commentNum}条`
                                            : commentNumState.savedCustomValue
                                            ? `${commentNumState.savedCustomValue}条`
                                            : "自定义"
                                    }}
                                </view>
                            </view>
                        </view>
                        <view class="text-xs text-[#00000080] mt-4" v-if="!isComment">
                            建议：私信数量不要超过8个/天/账号
                        </view>
                    </view>
                </view>
            </scroll-view>
            <scroll-view class="h-full" scroll-y v-show="step === 3">
                <view class="px-4 pb-[100rpx]">
                    <view class="font-medium text-[30rpx]">高级设置</view>
                    <view class="mt-[20rpx] rounded-[20rpx] bg-white px-[36rpx]">
                        <view
                            v-if="formData.customer_type === 0"
                            class="flex items-center justify-between py-[28rpx] border-[0] border-b border-solid border-[#F2F2F2]">
                            <view>
                                <view class="font-medium">跳过内容作者</view>
                                <view class="text-[22rpx] font-medium text-[#000000]/30 mt-1">
                                    开启后，将不会对内容作者的评论触达
                                </view>
                            </view>
                            <u-switch v-model="formData.skip_author" active-value="1" inactive-value="0" :size="40" />
                        </view>
                        <view
                            class="flex items-center justify-between py-[28rpx] border-[0] border-b border-solid border-[#F2F2F2]">
                            <view>
                                <view class="font-medium">过滤已执行客户</view>
                                <view class="text-[22rpx] font-medium text-[#000000]/30 mt-1">
                                    开启后，将不会对客户池内的客户重复触达
                                </view>
                            </view>
                            <u-switch
                                v-model="formData.filter_executed_customer"
                                active-value="1"
                                inactive-value="0"
                                :size="40" />
                        </view>
                        <view
                            v-if="!isCollect"
                            class="flex items-center justify-between py-[28rpx] border-[0] border-b border-solid border-[#F2F2F2]">
                            <view>
                                <view class="font-medium">触达时顺带点赞</view>
                                <view class="text-[22rpx] font-medium text-[#000000]/30 mt-1">
                                    开启后，将会对触达内容进行点赞
                                </view>
                            </view>
                            <u-switch v-model="formData.comment_like" active-value="1" inactive-value="0" :size="40" />
                        </view>
                        <view
                            v-if="!isCollect"
                            class="flex items-center justify-between py-[28rpx] border-[0] border-b border-solid border-[#F2F2F2]">
                            <view>
                                <view class="font-medium">触达时顺带关注</view>
                                <view class="text-[22rpx] font-medium text-[#000000]/30 mt-1">
                                    开启后，将会对触达用户进行关注
                                </view>
                            </view>
                            <u-switch
                                v-model="formData.comment_follow"
                                active-value="1"
                                inactive-value="0"
                                :size="40" />
                        </view>
                        <view
                            class="flex items-center justify-between py-[28rpx] border-[0] border-b border-solid border-[#F2F2F2] gap-2"
                            @click="handleChangeTime('content')">
                            <text class="font-medium flex-shrink-0">内容发布时间</text>
                            <view class="flex items-center gap-x-1">
                                <text
                                    class="line-clamp-1 break-all"
                                    :class="formData.comment_time > -1 ? 'text-primary font-medium' : 'text-[#B2B2B2]'"
                                    >{{ getTimeLabel("content") }}</text
                                >
                                <u-icon name="arrow-right" size="22" color="#B2B2B2"></u-icon>
                            </view>
                        </view>
                        <view
                            class="flex items-center justify-between py-[28rpx] border-[0] border-b border-solid border-[#F2F2F2] gap-2"
                            @click="handleChangeTime('comment')">
                            <text class="font-medium flex-shrink-0">评论发布时间</text>
                            <view class="flex items-center gap-x-1">
                                <text
                                    class="line-clamp-1 break-all"
                                    :class="formData.comment_time > -1 ? 'text-primary font-medium' : 'text-[#B2B2B2]'"
                                    >{{ getTimeLabel("comment") }}</text
                                >
                                <u-icon name="arrow-right" size="22" color="#B2B2B2"></u-icon>
                            </view>
                        </view>
                        <view
                            class="flex items-center justify-between py-[28rpx] border-[0] border-b border-solid border-[#F2F2F2] gap-2"
                            @click="showChooseRegionPopup = true">
                            <text class="font-medium flex-shrink-0">客户地区</text>
                            <view class="flex items-center gap-x-1">
                                <text class="line-clamp-1 break-all text-primary font-medium">{{
                                    formData.comment_region.length > 0 ? formData.comment_region.join(";") : "不限"
                                }}</text>
                                <u-icon name="arrow-right" size="22" color="#B2B2B2"></u-icon>
                            </view>
                        </view>
                        <view
                            v-if="false"
                            class="flex items-center justify-between py-[28rpx] gap-2"
                            @click="handleEditCommentGender">
                            <text class="font-medium flex-shrink-0">客户性别</text>
                            <view class="flex items-center gap-x-1">
                                <text
                                    class="font-medium line-clamp-1 break-all"
                                    :class="formData.comment_gender ? 'text-primary font-medium' : 'text-[#B2B2B2]'"
                                    >{{ formData.comment_gender || "请选择" }}</text
                                >
                                <u-icon name="arrow-right" size="22" color="#B2B2B2"></u-icon>
                            </view>
                        </view>
                        <view
                            v-if="false"
                            class="flex items-center justify-between py-[28rpx] border-[0] border-b border-solid border-[#F2F2F2] gap-2"
                            @click="handleEditCommentAge">
                            <text class="font-medium flex-shrink-0">用户年龄</text>
                            <view class="flex items-center gap-x-1">
                                <text class="line-clamp-1 break-all text-primary font-medium">{{
                                    formData.comment_age
                                }}</text>
                                <u-icon name="arrow-right" size="22" color="#B2B2B2"></u-icon>
                            </view>
                        </view>
                        <view
                            v-if="false"
                            class="flex items-center justify-between py-[28rpx] gap-2"
                            @click="handleEditCommentAccountFeature">
                            <text class="font-medium flex-shrink-0">账号特征</text>
                            <view class="flex items-center gap-x-1">
                                <text class="line-clamp-1 break-all text-primary font-medium">{{
                                    formData.comment_account_feature == "0" ? "全部" : "跳过认证号"
                                }}</text>
                                <u-icon name="arrow-right" size="22" color="#B2B2B2"></u-icon>
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>
            <scroll-view v-if="step === 4" scroll-y class="h-full">
                <view class="px-4 pb-[100rpx]">
                    <base-setting
                        v-model="formData"
                        :show-device="false"
                        :show-accounts="true"
                        :current-frequency="currentFrequency"
                        :platform-types="[AppTypeEnum.XHS, AppTypeEnum.DOUYIN]"
                        @change-frequency="currentFrequency = $event" />

                    <view class="mt-[50rpx]" v-if="taskErrorMsg">
                        <view class="font-medium">任务冲突：</view>
                        <view class="text-font-medium text-[#ff2442] text-xs mt-[20rpx]">
                            {{ taskErrorMsg }}
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>
        <view class="bg-white shadow-[0_0_0_1rpx_rgba(0,0,0,0.05)] flex-shrink-0 pb-5">
            <view class="flex items-center px-4 h-[140rpx]" :class="step === 1 ? 'justify-end' : 'justify-between'">
                <template v-if="step != steps.length">
                    <view
                        v-if="step !== 1"
                        class="px-[48rpx] py-[20rpx] rounded-md border border-solid border-[#F1F2F5] text-[#878787]"
                        @click="handleStep(step, 'prev')">
                        上一步
                    </view>
                    <view
                        class="px-[48rpx] py-[20rpx] rounded-md text-white"
                        :class="[canNext ? 'bg-primary' : 'bg-[#787878CC]']"
                        @click="handleStep(step, 'next')">
                        下一步
                    </view>
                </template>
                <template v-else>
                    <view
                        class="rounded-[16rpx] flex-1 h-[100rpx] bg-primary text-white font-medium flex items-center justify-center"
                        @click="handleCreateTask">
                        创建任务
                    </view>
                </template>
            </view>
        </view>
    </view>

    <u-popup v-model="commonPopup.show" mode="center" width="90%" :border-radius="20" @close="commonPopup.show = false">
        <view class="p-4 bg-white rounded-[20rpx]">
            <view class="text-[30rpx] font-medium text-center mt-2">{{ commonPopup.title }}</view>
            <view class="mt-[48rpx] bg-[#F3F3F3] px-4 py-2 rounded-[16rpx]">
                <u-input
                    v-model="commonPopup.inputValue"
                    placeholder="请输入"
                    type="digit"
                    placeholder-style="color: #0000004d; font-size: 26rpx;" />
            </view>
            <view class="flex items-center gap-x-5 mt-[56rpx]">
                <view
                    class="flex-1 h-[90rpx] flex items-center justify-center rounded-[12rpx] bg-[#F3F3F3] font-medium text-[#000000b3]"
                    @click="commonPopup.show = false">
                    取消
                </view>
                <view
                    class="flex-1 h-[90rpx] flex items-center justify-center rounded-[12rpx] bg-black font-medium text-white"
                    @click="handleCommonPopupConfirm"
                    >确定</view
                >
            </view>
        </view>
    </u-popup>
    <region-form v-model="showRegionFormPopup" :region="formData.region" @confirm="handleRegionConfirm" />
    <clue-gen-pop v-model="showClueGenPopup" @confirm="handleClueGenConfirm" />
    <keywords-edit
        ref="keywordsEditRef"
        v-model="showKeywordsEdit"
        :title="getKeywordsTitle"
        @confirm="handleKeywordsEditConfirm" />
    <comment-filter v-model="showCommentFilterEdit" @confirm="handleCommentFilterConfirm" />
    <confirm-dialog
        v-model="showCreateTaskSuccessDialog"
        center
        confirm-text="确定"
        content="创建成功，回到首页？"
        :show-close="false"
        @close="handleCreateTaskSuccess"
        @confirm="handleCreateTaskSuccess" />
    <choose-region v-model="showChooseRegionPopup" @confirm="handleChooseRegionConfirm" />
    <choose-age ref="chooseAgeRef" v-model="showAgePopup" @confirm="handleAgeConfirm" />
    <choose-comment-time
        v-model="showCommentTimePopup"
        :value="commentTimeType === 'content' ? formData.content_time_index : formData.comment_time_index"
        :list="commentTimeList"
        @confirm="handleCommentTimeConfirm" />
    <popup-bottom
        v-model="showHistoryIndustryPopup"
        title="获客行业记录"
        :is-disabled-touch="true"
        @close="showHistoryIndustryPopup = false">
        <template #content>
            <view class="h-full">
                <z-paging
                    ref="industryHistoryPagingRef"
                    v-model="historyIndustry"
                    :fixed="false"
                    @query="getIndustryHistory">
                    <view class="flex flex-wrap gap-[20rpx] p-4">
                        <view
                            v-for="(item, index) in historyIndustry"
                            :key="index"
                            class="rounded-full px-[24rpx] relative py-[10rpx] shadow-[0_0_0_2rpx_#0000001a]"
                            @click="handleSelectHistoryIndustry(item.keyword)">
                            {{ item.keyword }}
                            <view
                                class="absolute right-[-10rpx] top-[-10rpx] w-[32rpx] h-[32rpx] flex items-center justify-center rounded-full bg-[#0000004d]"
                                @click="handleDeleteHistoryIndustry(index)">
                                <u-icon name="close" color="#ffffff" size="16"></u-icon>
                            </view>
                        </view> </view
                ></z-paging>
            </view>
        </template>
    </popup-bottom>
    <popup-bottom v-model="showTemp" title="同城获客需知" custom-class="bg-[#F3F3F3]">
        <template #content>
            <scroll-view scroll-y class="h-full">
                <view class="p-4">
                    <view class="text-[30rpx] text-[#000000]/70 font-medium">
                        为保证某音平台的同城功能顺畅运行，需将【所在城市】放置推荐的左侧
                    </view>
                    <view class="bg-white rounded-[20rpx] p-[38rpx] mt-[50rpx]">
                        <view class="flex items-center gap-x-2">
                            <view
                                class="w-[40rpx] h-[40rpx] flex items-center justify-center rounded-full bg-primary text-white font-medium text-[30rpx]"
                                >1</view
                            >
                            <view class="text-[30rpx] font-medium text-[#000000]/90"
                                >在AI手机中打开某音平台，长按上方频道</view
                            >
                        </view>
                        <image
                            src="@/ai_modules/device/static/images/common/closure_step1.png"
                            mode="widthFix"
                            class="w-full mt-[50rpx]"></image>
                    </view>
                    <view class="bg-white rounded-[20rpx] p-[38rpx] mt-[50rpx]">
                        <view class="flex items-center gap-x-2">
                            <view
                                class="w-[40rpx] h-[40rpx] flex items-center justify-center rounded-full bg-primary text-white font-medium text-[30rpx]"
                                >2</view
                            >
                            <view class="text-[30rpx] font-medium text-[#000000]/90"
                                >将所在城市拖放到置顶（推荐的下方）</view
                            >
                        </view>
                        <image
                            src="@/ai_modules/device/static/images/common/closure_step2.png"
                            mode="widthFix"
                            class="w-full mt-[50rpx]"></image>
                    </view>
                </view>
            </scroll-view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import WechatOA from "@/utils/wechat";
import {
    createClosureTask,
    createInteractionTask,
    getClosureIndustryHistory,
    deleteClosureIndustryHistory,
} from "@/api/device";
import { AppTypeEnum } from "@/enums/appEnums";
import { useAppStore } from "@/stores/app";
import { ListenerTypeEnum, CreateTypeEnum } from "@/ai_modules/device/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import ClueGenPop from "@/ai_modules/device/components/clue-gen-pop/clue-gen-pop.vue";
import KeywordsEdit from "@/ai_modules/device/components/keywords-edit/keywords-edit.vue";
import CommentFilter from "@/ai_modules/device/components/comment-filter/comment-filter.vue";
import BaseSetting from "@/ai_modules/device/components/base-setting/base-setting.vue";
import ChooseRegion from "@/ai_modules/device/components/choose-region/choose-region.vue";
import ChooseAge from "@/ai_modules/device/components/choose-age/choose-age.vue";
import ChooseCommentTime from "@/ai_modules/device/components/choose-comment-time/choose-comment-time.vue";
import RegionForm from "@/ai_modules/device/components/region-form/region-form.vue";

const { on } = useEventBusManager();
const appStore = useAppStore();

const createType = ref<CreateTypeEnum>(CreateTypeEnum.COMMENT_MARKETING);
const step = ref(1);
const commentIndex = ref(0);
const currentFrequency = ref(0);
const taskErrorMsg = ref<string>("");

const steps = ref([
    { step: 1, title: "选择行业" },
    { step: 2, title: "设定评论" },
    { step: 3, title: "高级设置" },
    { step: 4, title: "设定时间" },
]);

const formData = reactive<{
    name: string;
    customer_type: number;
    region: string;
    industry: string[];
    industryNum: number;
    commentNum: number;
    comment_filter_list: { value: string; checked: boolean; id: number }[];
    comment_content_list: string[];
    skip_author: 0 | 1;
    filter_executed_customer: 0 | 1;
    comment_like: string;
    comment_follow: string;
    comment_time_index: number[];
    comment_region: string[];
    comment_gender: string;
    comment_age: string;
    comment_account_feature: string;
    comment_time: number;
    content_time_index: number[];
    content_time: number;
    accounts: string[];
    task_frep: number;
    custom_date: string[];
    time_config: string[];
    comment_type: number;
}>({
    name: "",
    region: "",
    customer_type: 0,
    industry: [],
    industryNum: 1,
    commentNum: 1,
    comment_filter_list: [],
    comment_content_list: [],
    skip_author: 1,
    filter_executed_customer: 1,
    comment_like: "1",
    comment_follow: "1",
    comment_time_index: [0],
    content_time_index: [0],
    content_time: 0,
    comment_region: [],
    comment_gender: "不限",
    comment_age: "不限",
    comment_account_feature: "0",
    comment_time: 0,
    accounts: [],
    task_frep: 1,
    custom_date: [],
    time_config: ["09:00", "09:30"],
    comment_type: 1,
});

const showTemp = ref(false);
const showRegionFormPopup = ref(false);
const showKeywordsEdit = ref(false);
const showCommentTimePopup = ref(false);
const showClueGenPopup = ref(false);
const showHistoryIndustryPopup = ref(false);
const showCommentFilterEdit = ref(false);
const showAgePopup = ref(false);
const showChooseRegionPopup = ref(false);
const showCreateTaskSuccessDialog = ref(false);

const industryInput = ref<string>("");
const scrollToIndustryId = ref<string>("");
const commentTimeType = ref<"content" | "comment">("comment");

const keywordsEditRef = ref<InstanceType<typeof KeywordsEdit>>();
const chooseAgeRef = ref<InstanceType<typeof ChooseAge>>();
const industryHistoryPagingRef = shallowRef();

const keywordsEditType = ref<"clue" | "comment" | "comment_content">("clue");
const keywordsEditIndex = ref<number>(-1);

const historyIndustry = ref<any[]>([]);

const touchTypeList = ref<any[]>([
    { name: 1, label: "点赞评论", checked: false },
    { name: 2, label: "关注", checked: false },
    { name: 3, label: "点赞作品", checked: false },
    { name: 4, label: "评论作品", checked: false },
    { name: 5, label: "收藏作品", checked: false },
]);

const commentTimeList = [
    { value: 0, label: "不限" },
    { value: 1, label: "24小时内" },
    { value: 2, label: "2天内" },
    { value: 3, label: "3天内" },
    { value: 4, label: "4天内" },
    { value: 5, label: "5天内" },
    { value: 6, label: "6天内" },
    { value: 7, label: "7天内" },
];

const industryNumList = [1, 3, 5, 10, 20];
const commentNumList = [1, 3, 5, 10, 20];

const commonPopup = reactive({
    show: false,
    title: "",
    inputValue: 1,
    callback: null as ((val: number) => void) | null,
});

const isCollect = computed(() => createType.value === CreateTypeEnum.COLLECT_MARKETING);
const isComment = computed(() => commentIndex.value === 0);
const canNext = computed(() => canStepProceed(step.value));

const getKeywordsTitle = computed(() => {
    const titles = {
        clue: "线索词",
        comment: "评论词",
        comment_content: "触达内容",
    };
    return titles[keywordsEditType.value];
});

const getTouchTypeList = computed(() => {
    return formData.customer_type == 1
        ? touchTypeList.value.filter((item: any) => item.name != 1)
        : touchTypeList.value;
});

function useNumSelection(list: number[], initialVal: number, title: string, updateFn: (v: number) => void) {
    const currentIndex = ref(list.indexOf(initialVal));
    const savedCustomValue = ref<number | null>(null);

    const handleSelect = (item: number, index: number) => {
        currentIndex.value = index;
        updateFn(item);
    };

    const openCustom = () => {
        commonPopup.title = title;
        commonPopup.inputValue = savedCustomValue.value || 1;
        commonPopup.callback = (val) => {
            updateFn(val);
            savedCustomValue.value = val;
            currentIndex.value = list.length;
        };
        commonPopup.show = true;
    };

    return reactive({
        currentIndex,
        savedCustomValue,
        handleSelect,
        openCustom,
    });
}

const canStepProceed = (stepNumber: number): boolean => {
    switch (stepNumber) {
        case 1:
            return formData.customer_type === 0 ? formData.industry.length > 0 : true;
        case 2:
            // 这里要判断如果是留痕获客，就要判断留痕方式至少选择一个
            return isCollect.value
                ? getTouchTypeList.value.some((item: any) => item.checked)
                : formData.comment_filter_list.length > 0 && formData.comment_content_list.length > 0;
        case 3:
            return true;
        default:
            return false;
    }
};

const getTimeLabel = (type: "content" | "comment"): string => {
    if (type === "content") {
        return formData.content_time > -1 ? commentTimeList[formData.content_time_index[0]].label : "不限";
    } else {
        return formData.comment_time > -1 ? commentTimeList[formData.comment_time_index[0]].label : "不限";
    }
};

const industryNumState = useNumSelection(
    industryNumList,
    formData.industryNum,
    "输入每个行业看笔记的数量",
    (val) => (formData.industryNum = val)
);

const commentNumState = useNumSelection(
    commentNumList,
    formData.commentNum,
    "输入评论数量上限",
    (val) => (formData.commentNum = val)
);

const handleStep = (targetStep: number, type?: "next" | "prev") => {
    // 上一步
    if (type === "prev") {
        step.value--;
        return;
    }

    if (type === "next") {
        if (canNext.value) {
            step.value++;
        } else {
            const messages: Record<number, () => string> = {
                1: () => "请至少添加一个线索",
                2: () => {
                    if (formData.customer_type == 0) {
                        if (formData.comment_filter_list.length == 0) {
                            return "评论词筛选至少添加一个";
                        }
                        if (!isCollect.value && formData.comment_content_list.length == 0) {
                            return "触达方式和话术至少添加一个";
                        }
                    }
                    return "请配置相关数据";
                },
                3: () => "请设定时间",
            };
            uni.$u.toast(messages[step.value]?.() || "请完成当前步骤");
        }
        return;
    }

    if (targetStep === step.value) return;

    if (targetStep < step.value) {
        step.value = targetStep;
    } else {
        for (let i = 1; i < targetStep; i++) {
            if (!canStepProceed(i)) {
                uni.$u.toast("请按顺序完成步骤");
                return;
            }
        }
        step.value = targetStep;
    }
};

const handleAddIndustry = () => {
    if (industryInput.value.trim() === "") {
        uni.$u.toast("请输入获客行业");
        return;
    }
    if (formData.industry.includes(industryInput.value)) {
        uni.$u.toast("已存在");
        return;
    }
    formData.industry.push(industryInput.value);
    industryInput.value = "";
    nextTick(() => {
        scrollToIndustryId.value = "industry_" + (formData.industry.length - 1);
    });
};

const handleEditClue = (index: number) => {
    keywordsEditIndex.value = index;
    keywordsEditType.value = "clue";
    showKeywordsEdit.value = true;
    keywordsEditRef.value?.setFormData(formData.industry[index]);
};

const handleDeleteClue = (index: number) => {
    formData.industry.splice(index, 1);
};

const handleSelectHistoryIndustry = (keyword: string) => {
    if (formData.industry.includes(keyword)) {
        uni.$u.toast("已存在");
        return;
    }
    formData.industry.push(keyword);
    showHistoryIndustryPopup.value = false;
};

const handleDeleteHistoryIndustry = async (index: number) => {
    uni.showLoading({ title: "删除中...", mask: true });
    try {
        await deleteClosureIndustryHistory({
            id: historyIndustry.value[index].id,
        });
        historyIndustry.value.splice(index, 1);
        uni.hideLoading();
        uni.showToast({ title: "删除成功", icon: "none", duration: 3000 });
    } catch (error: any) {
        uni.showToast({ title: error || "删除失败", icon: "none", duration: 3000 });
    }
};

const handleClueGenConfirm = (clueList: any[]) => {
    formData.industry.push(...clueList);
    showClueGenPopup.value = false;
};

const openCommentFilterEdit = () => {
    showCommentFilterEdit.value = true;
    keywordsEditRef.value?.setFormData(formData.comment_filter_list);
};

const handleCommentFilterEdit = (index: number) => {
    keywordsEditIndex.value = index;
    keywordsEditType.value = "comment";
    showKeywordsEdit.value = true;
    keywordsEditRef.value?.setFormData(formData.comment_filter_list[index].value);
};

const handleCommentFilterDelete = (index: number) => {
    formData.comment_filter_list.splice(index, 1);
};

const handleCommentFilterConfirm = (data: any) => {
    formData.comment_filter_list = data;
};

const handleEditCommentContent = (index: number) => {
    keywordsEditIndex.value = index;
    keywordsEditType.value = "comment_content";
    showKeywordsEdit.value = true;
    keywordsEditRef.value?.setFormData(formData.comment_content_list[index]);
};

const handleCommentContentDelete = (index: number) => {
    formData.comment_content_list.splice(index, 1);
};

const handleKeywordsEditConfirm = (data: any) => {
    if (keywordsEditType.value === "clue") {
        if (keywordsEditIndex.value === -1) {
            formData.industry.push(data);
        } else {
            formData.industry[keywordsEditIndex.value] = data;
        }
    } else if (keywordsEditType.value === "comment") {
        if (keywordsEditIndex.value === -1) {
            formData.comment_filter_list.push(data);
        } else {
            formData.comment_filter_list[keywordsEditIndex.value].value = data;
        }
    } else if (keywordsEditType.value === "comment_content") {
        if (keywordsEditIndex.value === -1) {
            formData.comment_content_list.push(data);
        } else {
            formData.comment_content_list[keywordsEditIndex.value] = data;
        }
    }
    showKeywordsEdit.value = false;
};

const handleCommonPopupConfirm = () => {
    const val = Number(commonPopup.inputValue);
    if (val < 1) {
        uni.$u.toast("请输入大于等于1的数字");
        return;
    }
    if (commonPopup.callback) {
        commonPopup.callback(val);
    }
    commonPopup.show = false;
};

// ==================== 时间相关处理 ====================
/**
 * 改变时间
 */
const handleChangeTime = (type: "content" | "comment") => {
    commentTimeType.value = type;
    if (type === "content") {
        showCommentTimePopup.value = true;
    } else {
        showCommentTimePopup.value = true;
        commentIndex.value = 0;
    }
};

const handleCommentTimeConfirm = (res: any) => {
    if (commentTimeType.value === "content") {
        formData.content_time_index = res;
        formData.content_time = commentTimeList[formData.content_time_index[0]].value;
    } else {
        formData.comment_time_index = res;
        formData.comment_time = commentTimeList[formData.comment_time_index[0]].value;
    }
};

const handleRegionConfirm = (region: string) => {
    formData.region = region;
    showRegionFormPopup.value = false;
};

const handleChooseRegionConfirm = (data: any) => {
    if (data.isAll || data.regionList.length === 0) {
        formData.comment_region = [];
    } else {
        formData.comment_region = data.regionList;
    }
    showChooseRegionPopup.value = false;
};

const handleEditCommentAge = () => {
    chooseAgeRef.value?.setFormData(formData.comment_age);
    showAgePopup.value = true;
};

const handleAgeConfirm = (data: string) => {
    formData.comment_age = data;
    showAgePopup.value = false;
};

const handleEditCommentGender = () => {
    const genderList = ["不限", "男", "女"];
    uni.showActionSheet({
        itemList: genderList,
        success: (res: any) => {
            formData.comment_gender = genderList[res.tapIndex];
        },
    });
};

const handleEditCommentAccountFeature = () => {
    uni.showActionSheet({
        itemList: ["全部", "跳过认证号"],
        success: (res: any) => {
            if (res.tapIndex === 0) {
                formData.comment_account_feature = "0";
            } else {
                formData.comment_account_feature = "1";
            }
        },
    });
};

const handleCreateTask = async () => {
    // 表单验证
    if (!formData.name) {
        uni.$u.toast("请输入任务名称");
        return;
    }
    if (!formData.accounts.length) {
        uni.$u.toast("请选择发布账号");
        return;
    }
    if (currentFrequency.value === 5 && !formData.custom_date.length) {
        uni.$u.toast("请选择任务日期");
        return;
    }
    if (!formData.time_config[0] || !formData.time_config[1]) {
        uni.$u.toast("请选择任务时间");
        return;
    }

    uni.showLoading({ title: "创建中...", mask: true });

    try {
        const params = {
            name: formData.name,
            accounts: formData.accounts,
            city: formData.region,
            industry_type: formData.customer_type,
            task_frep: formData.task_frep,
            task_type: isCollect.value ? 3 : commentIndex.value === 0 ? 1 : 2,
            time_config: [`${formData.time_config[0]}-${formData.time_config[1]}`],
            task_date: formData.custom_date,
            industry: formData.industry.join(";"),
            industry_num: formData.industryNum,
            filter: formData.comment_filter_list.map((item: any) => item.value),
            content: formData.comment_content_list,
            send_num: formData.commentNum,
            is_like: formData.comment_like,
            is_follow: formData.comment_follow,
            gender: formData.comment_gender,
            old: formData.comment_age,
            is_content_author: formData.skip_author,
            is_execed_clues: formData.filter_executed_customer,
            content_publish_day: formData.content_time,
            comment_publish_day: formData.comment_time,
            ip_address: formData.comment_region,
            marker_method: getTouchTypeList.value.filter((item) => item.checked).map((item) => item.name),
        };

        isCollect.value ? await createInteractionTask(params) : await createClosureTask(params);
        uni.hideLoading();
        showCreateTaskSuccessDialog.value = true;
        WechatOA.notify();
    } catch (error: any) {
        taskErrorMsg.value = error;
        uni.hideLoading();

        if (error.indexOf("24小时自动执行任务") > -1) {
            uni.showModal({
                title: "提示",
                content: "您已开启24小时自动执行任务，无法创建手动任务，如您需手动创建任务，需先关闭24小时托管。",
                success: (res) => {
                    if (res.confirm) {
                        uni.$u.route({ url: "/pages/phone/phone" });
                    }
                },
            });
        } else {
            taskErrorMsg.value = error;
            uni.showToast({ title: error, icon: "none", duration: 3000 });
        }
    }
};

const handleCreateTaskSuccess = () => {
    uni.$u.route({
        url: "/pages/phone/phone",
        type: "reLaunch",
    });
    showCreateTaskSuccessDialog.value = false;
};

const getIndustryHistory = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await getClosureIndustryHistory({
            task_type: isComment.value ? 1 : 2,
            page_no,
            page_size,
        });
        industryHistoryPagingRef.value?.complete(lists);
    } catch (error) {
        industryHistoryPagingRef.value?.complete([]);
    }
};

watch(
    () => [appStore.getCommentFilterConfig, appStore.getCommentContentConfig],
    (newVal) => {
        if (newVal[0] && newVal[0].length > 0) {
            formData.comment_filter_list = newVal[0].map((item: string) => ({
                value: item,
                checked: true,
            }));
        }
        if (newVal[1] && newVal[1].length > 0) {
            formData.comment_content_list = newVal[1];
        }
    },
    { immediate: true }
);

onLoad((options: any) => {
    createType.value = options.type as CreateTypeEnum;
    formData.name = `${isCollect.value ? "留痕" : "截流"}获客任务${uni.$u.timeFormat(new Date(), "yyyymmddhhMM")}`;
    // 事件监听
    on("confirm", (res: any) => {
        const { type, data } = res;

        if (type === ListenerTypeEnum.CHOOSE_DATE) {
            if (data.length === 0) {
                currentFrequency.value = 0;
                formData.custom_date = [];
                return;
            }
            formData.custom_date = data;
            currentFrequency.value = 5;
        }

        if (type === ListenerTypeEnum.CHOOSE_ACCOUNT) {
            if (data.length === 0) return;
            formData.accounts = data.map((item: any) => ({
                id: item.id,
                account: item.account,
                type: item.type,
            }));
        }
    });
});
</script>
<style lang="scss" scoped>
.tab-slider {
    @apply h-[calc(100%-10rpx)] w-[50%] rounded-[16rpx] bg-white absolute top-[5rpx] left-0 transition-all duration-500;
}
</style>
