<template>
    <view class="flex flex-col h-screen bg-[#F7F9FC]">
        <u-navbar
            title="数字人口播混剪"
            title-bold
            :is-fixed="false"
            :border-bottom="false"
            :background="{ background: '#ffffff' }" />

        <view
            class="flex-shrink-0 bg-white border-[0] border-b border-solid border-[#F0F2F5] px-6 pt-[24rpx] pb-[20rpx]">
            <steps :steps="steps" :step="currentStep" @step="handleStep" />
        </view>

        <view class="grow min-h-0 relative">
            <view v-show="currentStep === 1" class="h-full flex flex-col">
                <view class="p-4 pt-2 space-y-2">
                    <view class="flex items-center justify-between h-[80rpx]">
                        <view class="flex items-center gap-[10rpx]">
                            <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                            <text class="text-[32rpx] font-extrabold text-[#0D1117]">选择形象</text>
                        </view>
                        <view
                            class="flex items-center gap-[8rpx] bg-[#EBF2FF] rounded-full px-[24rpx] py-[10rpx]"
                            @click="handleCreateAnchor">
                            <text class="text-xs font-semibold text-primary">＋ 新增形象</text>
                        </view>
                    </view>
                    <view class="flex items-center gap-[8rpx] bg-[#EBF2FF] rounded-[16rpx] px-[20rpx] py-[16rpx]">
                        <u-icon name="info-circle" color="#0065fb" size="20" />
                        <text class="text-xs text-primary font-medium">
                            可多选，已选
                            <text class="font-extrabold">{{ formData.anchorLists.length }}</text>
                            个数字人形象
                        </text>
                    </view>
                </view>
                <view class="grow min-h-0">
                    <z-paging ref="anchorPagingRef" v-model="anchorLists" :fixed="false" @query="getAnchorList">
                        <view class="grid grid-cols-3 gap-[16rpx] px-4">
                            <view
                                v-for="(item, index) in anchorLists"
                                :key="index"
                                class="relative rounded-[24rpx] overflow-hidden transition-transform duration-200 aspect-[3/4]"
                                :class="formData.anchorLists.includes(item) ? 'scale-[0.95]' : ''"
                                @click="handleAnchorSelect(item)">
                                <image :src="item.pic" lazy-load class="w-full h-full" mode="aspectFill" />
                                <view
                                    class="absolute bottom-0 left-0 right-0 h-[120rpx]"
                                    style="background: linear-gradient(to top, rgba(0, 0, 0, 0.45), transparent)" />
                                <template v-if="item.status === 6">
                                    <view
                                        class="absolute bottom-[12rpx] right-[12rpx] w-[44rpx] h-[44rpx] rounded-full bg-[#ffffff]/30 flex items-center justify-center border border-solid border-[#ffffff]/40"
                                        @click.stop="previewMaterial({ pic: item.pic, url: item.anchor_url })">
                                        <u-icon
                                            name="play-right-fill"
                                            color="#ffffff"
                                            size="20"
                                            class="ml-0.5"></u-icon>
                                    </view>
                                    <view
                                        class="absolute top-[12rpx] right-[12rpx] w-[40rpx] h-[40rpx] rounded-full flex items-center justify-center transition-all duration-200"
                                        :class="
                                            formData.anchorLists.includes(item)
                                                ? 'bg-primary shadow-[0_4rpx_12rpx_rgba(28,111,235,0.5)]'
                                                : 'bg-[#000000]/20 border-[3rpx] border-solid border-[#ffffff]/70'
                                        ">
                                        <u-icon
                                            v-if="formData.anchorLists.includes(item)"
                                            name="checkmark"
                                            color="#fff"
                                            size="13" />
                                    </view>
                                    <view
                                        v-if="formData.anchorLists.includes(item)"
                                        class="absolute inset-0 rounded-[24rpx] border-[4rpx] border-solid border-primary pointer-events-none" />
                                </template>
                                <view
                                    v-else
                                    class="absolute inset-0 bg-[#000000]/55 flex flex-col items-center justify-center gap-[10rpx]">
                                    <image
                                        src="@/ai_modules/digital_human/static/icons/pic2.svg"
                                        class="w-[48rpx] h-[48rpx]" />
                                    <text class="text-[22rpx] text-[#ffffff]/80">生成中</text>
                                </view>
                            </view>
                        </view>
                        <template #empty>
                            <view class="flex flex-col items-center justify-center py-[80rpx] gap-[16rpx]">
                                <view
                                    class="w-[140rpx] h-[140rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center">
                                    <image
                                        src="@/ai_modules/digital_human/static/images/common/avatar.png"
                                        class="w-[88rpx] h-[100rpx]" />
                                </view>
                                <text class="text-[30rpx] font-bold text-[#0D1117]">还没有数字人</text>
                                <text class="text-[#9CA3AF] text-center">快去定制一个专属数字人吧～</text>
                                <view
                                    class="mt-[12rpx] px-[48rpx] py-[20rpx] rounded-[24rpx] bg-primary shadow-[0_8rpx_24rpx_rgba(28,111,235,0.3)]"
                                    @click="handleCreateAnchor">
                                    <text class="text-[28rpx] font-bold text-white">定制数字人</text>
                                </view>
                            </view>
                        </template>
                    </z-paging>
                </view>
            </view>

            <view v-show="currentStep === 2" class="h-full">
                <scroll-view scroll-y class="h-full">
                    <view class="p-4 pt-2 space-y-2">
                        <view class="flex items-center justify-between h-[80rpx]">
                            <view class="flex items-center gap-[10rpx]">
                                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                <text class="text-[32rpx] font-extrabold text-[#0D1117]">身份人设</text>
                            </view>
                            <view
                                class="flex items-center gap-[8rpx] bg-white border border-solid border-[#E5E9F0] rounded-full px-[20rpx] py-[10rpx]"
                                @click="showCharacter = true">
                                <u-icon name="clock" color="#4B5563" size="20" />
                                <text class="text-xs font-semibold text-[#4B5563]">历史人设</text>
                            </view>
                        </view>
                        <view
                            class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <view
                                class="flex items-center gap-[12rpx] px-[28rpx] py-[22rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <text class="text-[28rpx] font-bold text-[#0D1117] flex-1">人设名称</text>
                                <text class="text-[22rpx] text-[#9CA3AF]">最多 20 字</text>
                            </view>
                            <view class="px-[28rpx] py-[20rpx]">
                                <view class="bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[16rpx]">
                                    <u-input
                                        v-model="formData.person_name"
                                        placeholder-style="font-size:26rpx;color:#C0C4CC;"
                                        placeholder="如：小美、助理小李、专业顾问"
                                        maxlength="20"
                                        @change="isCharacter = false" />
                                </view>
                            </view>
                        </view>
                        <view
                            class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <view
                                class="flex items-center gap-[12rpx] px-[28rpx] py-[22rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <text class="text-[28rpx] font-bold text-[#0D1117] flex-1">人设介绍</text>
                                <text class="text-[22rpx] text-[#9CA3AF]">最多 50 字</text>
                            </view>
                            <view class="px-[28rpx] py-[20rpx]">
                                <view class="bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[16rpx]">
                                    <u-input
                                        v-model="formData.person_introduction"
                                        placeholder-style="font-size:26rpx;color:#C0C4CC;"
                                        placeholder="如：专业美妆博主，擅长护肤推荐，风格亲切自然"
                                        maxlength="50"
                                        type="textarea"
                                        height="80"
                                        @change="isCharacter = false" />
                                </view>
                            </view>
                        </view>
                    </view>
                </scroll-view>
            </view>

            <view v-show="currentStep === 3" class="h-full flex flex-col">
                <view class="px-4 pt-2 space-y-2">
                    <view class="flex items-center justify-between h-[80rpx]">
                        <view class="flex items-center gap-[10rpx]">
                            <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                            <text class="text-[32rpx] font-extrabold text-[#0D1117]">选择文案</text>
                        </view>
                        <text class="text-xs text-[#9CA3AF]">
                            共
                            <text class="text-primary font-bold">{{
                                copywriterTypeIndex === 0 ? formData.copywriterList.length : formData.audio.length
                            }}</text>
                            条
                        </text>
                    </view>
                    <view class="flex bg-[#F0F2F5] rounded-[20rpx] p-[6rpx]">
                        <view
                            v-for="(item, index) in COPYWRITER_TABS"
                            :key="index"
                            class="flex-1 h-[72rpx] rounded-[16rpx] flex items-center justify-center font-semibold transition-all duration-200"
                            :class="
                                copywriterTypeIndex === index
                                    ? 'bg-white text-primary shadow-[0_2rpx_8rpx_rgba(0,0,0,0.08)]'
                                    : 'text-[#9CA3AF]'
                            "
                            @click="copywriterTypeIndex = index">
                            {{ item }}
                        </view>
                    </view>
                    <view class="flex gap-[12rpx] !my-4">
                        <template v-if="copywriterTypeIndex === 0">
                            <view
                                class="flex-1 flex items-center justify-center gap-[10rpx] h-[96rpx] rounded-[24rpx] bg-white border border-solid border-[#E5E9F0] shadow-[0_2rpx_8rpx_rgba(0,0,0,0.04)]"
                                @click="handleShowCopywriter()">
                                <u-icon name="edit-pen" color="#4B5563" size="22" />
                                <text class="text-[28rpx] font-bold text-[#334155]">手动输入</text>
                            </view>
                            <view
                                class="flex-1 h-[96rpx] flex items-center justify-center gap-[10rpx] rounded-[24rpx] relative overflow-hidden shadow-[0_8rpx_20rpx_rgba(0,101,251,0.25)]"
                                style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                                @click="showChooseAgent = true">
                                <image src="/static/images/common/magic_white.png" class="w-[32rpx] h-[32rpx]" />
                                <text class="text-[28rpx] font-bold text-white">AI 智能生成</text>
                            </view>
                        </template>
                        <view
                            v-if="copywriterTypeIndex === 1"
                            class="flex-1 h-[96rpx] rounded-[20rpx] flex items-center justify-center gap-[8rpx] bg-white border border-dashed border-[#0065fb]/40"
                            @click="showAudioType = true">
                            <view
                                class="w-[44rpx] h-[44rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center">
                                <u-icon name="plus" size="18" color="#0065fb" />
                            </view>
                            <text class="text-[28rpx] font-bold text-[#0D1117]">添加音频</text>
                        </view>
                    </view>
                </view>
                <view class="grow min-h-0">
                    <scroll-view
                        scroll-y
                        class="h-full"
                        v-if="formData.copywriterList.length > 0 || formData.audio.length > 0">
                        <view class="px-4 flex flex-col gap-[16rpx] pb-4">
                            <template v-if="copywriterTypeIndex === 0">
                                <view
                                    v-for="(item, index) in formData.copywriterList"
                                    :key="index"
                                    class="relative bg-white rounded-[24rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]"
                                    :class="
                                        !isSingleCopywriterValid(item.content)
                                            ? 'border border-solid border-[#F56C6C]'
                                            : ''
                                    "
                                    @click="handleSelectCopywriter(index)">
                                    <view class="absolute left-0 top-0 w-[6rpx] h-full bg-primary rounded-l-[24rpx]" />
                                    <view class="pl-[32rpx] pr-[24rpx] pt-[22rpx] pb-[18rpx]">
                                        <view class="flex items-center gap-[12rpx] mb-[18rpx]">
                                            <view
                                                class="w-[40rpx] h-[40rpx] rounded-full bg-[#EBF2FF] flex items-center justify-center flex-shrink-0">
                                                <text class="text-[22rpx] font-bold text-primary">{{ index + 1 }}</text>
                                            </view>
                                            <text class="flex-1 text-[28rpx] font-bold text-[#0D1117] truncate">{{
                                                item.title
                                            }}</text>
                                            <view
                                                class="w-[40rpx] h-[40rpx] rounded-full bg-[#F3F4F6] flex items-center justify-center"
                                                @click.stop="handleDeleteCopywriter(index)">
                                                <u-icon name="close" color="#9CA3AF" size="14" />
                                            </view>
                                        </view>
                                        <text class="text-[#4B5563] leading-relaxed">{{ item.content }}</text>
                                        <view
                                            class="flex items-center justify-between mt-[14rpx] pt-[20rpx] border-[0] border-t border-solid border-[#F0F2F5]">
                                            <view
                                                class="flex items-center gap-[6rpx]"
                                                v-if="!isSingleCopywriterValid(item.content)">
                                                <u-icon name="info-circle-fill" color="#F56C6C" size="14" />
                                                <text class="text-[22rpx] text-[#F56C6C]">
                                                    {{
                                                        item.content.length > COPYWRITER_LIMIT
                                                            ? `超出${COPYWRITER_LIMIT}字限制`
                                                            : "不能少于3个字"
                                                    }}
                                                </text>
                                            </view>
                                            <view v-else />
                                            <text
                                                class="text-[22rpx]"
                                                :class="
                                                    item.content.length > COPYWRITER_LIMIT
                                                        ? 'text-[#F56C6C] font-bold'
                                                        : 'text-[#C0C4CC]'
                                                ">
                                                {{ item.content.length }} / {{ COPYWRITER_LIMIT }}
                                            </text>
                                        </view>
                                    </view>
                                </view>
                            </template>
                            <template v-if="copywriterTypeIndex === 1">
                                <view
                                    v-for="(item, index) in formData.audio"
                                    :key="index"
                                    class="relative bg-white rounded-[24rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                                    <view class="absolute left-0 top-0 w-[6rpx] h-full bg-primary rounded-l-[24rpx]" />
                                    <view class="pl-[20rpx] pr-[24rpx] pt-[22rpx] pb-[18rpx]">
                                        <view class="flex items-center gap-[12rpx] mb-[14rpx]">
                                            <view
                                                class="w-[64rpx] h-[64rpx] rounded-full flex items-center justify-center transition-all duration-200"
                                                :class="
                                                    isPlaying && currentAudioIndex === index
                                                        ? 'bg-primary'
                                                        : 'bg-[#EBF2FF]'
                                                "
                                                @click="handlePlayAudio(item.url, index)">
                                                <u-icon
                                                    :name="
                                                        isPlaying && currentAudioIndex === index
                                                            ? 'pause-circle'
                                                            : 'play-circle'
                                                    "
                                                    :color="
                                                        isPlaying && currentAudioIndex === index ? '#fff' : '#0065fb'
                                                    "
                                                    size="36" />
                                            </view>
                                            <text class="flex-1 text-[28rpx] font-bold text-[#0D1117]"
                                                >音频 {{ index + 1 }}</text
                                            >
                                            <view
                                                class="w-[40rpx] h-[40rpx] rounded-full bg-[#F3F4F6] flex items-center justify-center"
                                                @click.stop="handleDeleteCopywriter(index)">
                                                <u-icon name="close" color="#9CA3AF" size="14" />
                                            </view>
                                        </view>
                                        <view class="bg-[#F7F9FC] rounded-[16rpx] px-[16rpx] py-[12rpx]">
                                            <u-input
                                                v-model="item.content"
                                                type="textarea"
                                                placeholder="请输入音频内容"
                                                maxlength="500"
                                                height="160" />
                                        </view>
                                    </view>
                                </view>
                            </template>
                        </view>
                    </scroll-view>
                    <copywriter-empty v-else />
                </view>
            </view>

            <view v-show="currentStep === 4" class="h-full flex flex-col">
                <view class="px-4 pt-2">
                    <view class="flex items-center justify-between h-[80rpx]">
                        <view class="flex items-center gap-[10rpx]">
                            <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                            <text class="text-[32rpx] font-extrabold text-[#0D1117]">参考素材</text>
                        </view>
                        <text class="text-[22rpx] text-[#9CA3AF]"
                            >限制{{ montageConfig.materialTotalDuration }}分钟</text
                        >
                    </view>
                    <material-duration-bar ref="materialDurationBarRef" :material-list="formData.materialList" />
                </view>
                <view class="grow min-h-0">
                    <scroll-view scroll-y class="h-full">
                        <view class="p-4">
                            <material-container
                                :material-list="formData.materialList"
                                @preview="previewMaterial"
                                @replace="handleReplaceMaterial"
                                @delete="handleDeleteMaterial"
                                @upload="chooseUploadType()" />
                        </view>
                    </scroll-view>
                </view>
            </view>

            <view v-show="currentStep === 5" class="h-full">
                <scroll-view scroll-y class="h-full">
                    <view class="p-4 pt-2 space-y-3">
                        <view class="flex items-center justify-between h-[80rpx]">
                            <view class="flex items-center gap-[10rpx]">
                                <view class="w-[6rpx] h-[32rpx] bg-primary rounded-full" />
                                <text class="text-[32rpx] font-extrabold text-[#0D1117]">生成设置</text>
                            </view>
                        </view>
                        <view
                            class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <view class="flex items-center gap-[12rpx] px-[28rpx] py-[22rpx] border-b border-[#F0F2F5]">
                                <text class="text-[28rpx] font-bold text-[#0D1117]">视频名称</text>
                            </view>
                            <view class="px-[28rpx] py-[20rpx]">
                                <view class="bg-[#F7F9FC] rounded-[16rpx] px-[20rpx] py-[16rpx]">
                                    <u-input
                                        v-model="formData.name"
                                        maxlength="50"
                                        placeholder-style="font-size:26rpx;color:#C0C4CC;"
                                        placeholder="请输入视频名称" />
                                </view>
                            </view>
                        </view>
                        <view
                            class="bg-white rounded-[28rpx] shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <video-cover-upload v-model="formData.cover" />
                        </view>
                        <view
                            class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <view
                                class="flex items-center gap-[12rpx] px-[28rpx] py-[22rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <text class="text-[28rpx] font-bold text-[#0D1117]">内容汇总</text>
                            </view>
                            <view class="px-[28rpx] py-[20rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <view class="flex items-center justify-between mb-[14rpx]">
                                    <text class="text-xs text-[#9CA3AF] font-semibold">数字人形象</text>
                                    <view class="flex items-center gap-[4rpx]" @click="handleStep(1)">
                                        <text class="text-xs text-primary font-semibold"
                                            >{{ formData.anchorLists.length }} 个</text
                                        >
                                        <u-icon name="arrow-right" size="20" color="#0065fb" />
                                    </view>
                                </view>
                                <scroll-view scroll-x>
                                    <view class="flex gap-[12rpx] pb-[4rpx]">
                                        <view
                                            v-for="(item, index) in formData.anchorLists"
                                            :key="index"
                                            class="flex-shrink-0 w-[100rpx] h-[136rpx] rounded-[16rpx] overflow-hidden shadow-[0_2rpx_8rpx_rgba(0,0,0,0.10)]">
                                            <image :src="item.pic" class="w-full h-full" mode="aspectFill" />
                                        </view>
                                    </view>
                                </scroll-view>
                            </view>
                            <view
                                class="flex items-center gap-[12rpx] px-[28rpx] h-[100rpx] border-[0] border-b border-solid border-[#F0F2F5]"
                                @click="handleStep(2)">
                                <text class="flex-1 text-[28rpx] font-semibold text-[#0D1117]">身份人设</text>
                                <view class="flex items-center gap-[6rpx]">
                                    <text
                                        class=""
                                        :class="formData.person_name ? 'text-primary font-semibold' : 'text-[#9CA3AF]'"
                                        >{{ formData.person_name || "未设置" }}</text
                                    >
                                    <u-icon name="arrow-right" size="20" color="#C0C4CC" />
                                </view>
                            </view>
                            <view
                                class="flex items-center gap-[12rpx] px-[28rpx] h-[100rpx] border-[0] border-b border-solid border-[#F0F2F5]"
                                @click="handleStep(3)">
                                <text class="flex-1 text-[28rpx] font-semibold text-[#0D1117]">口播文案</text>
                                <view class="flex items-center gap-[4rpx]">
                                    <text class="text-[#9CA3AF]">共</text>
                                    <text class="text-primary font-bold">{{
                                        formData.copywriterList.length || formData.audio.length
                                    }}</text>
                                    <text class="text-[#9CA3AF]">条</text>
                                    <u-icon name="arrow-right" size="20" color="#C0C4CC" />
                                </view>
                            </view>
                            <view
                                class="flex items-center gap-[12rpx] px-[28rpx] h-[100rpx] border-[0] border-b border-solid border-[#F0F2F5]"
                                @click="handleStep(4)">
                                <text class="flex-1 text-[28rpx] font-semibold text-[#0D1117]">参考素材</text>
                                <view class="flex items-center gap-[4rpx]">
                                    <text class="text-[#9CA3AF]">共</text>
                                    <text class="text-primary font-bold">{{ formData.materialList.length }}</text>
                                    <text class="text-[#9CA3AF]">个</text>
                                    <u-icon name="arrow-right" size="20" color="#C0C4CC" />
                                </view>
                            </view>
                            <view
                                class="flex items-center gap-[12rpx] px-[28rpx] h-[100rpx] border-[0] border-b border-solid border-[#F0F2F5]"
                                @click="openChooseTone()">
                                <text class="flex-1 text-[28rpx] font-semibold text-[#0D1117]">选择音色</text>
                                <view class="flex items-center gap-[6rpx]">
                                    <view
                                        v-if="!voiceValue.name"
                                        class="bg-[#EBF2FF] px-[14rpx] py-[6rpx] rounded-[8rpx]">
                                        <text class="text-[22rpx] text-primary font-semibold">视频原音</text>
                                    </view>
                                    <text v-else class="text-primary font-semibold">{{ voiceValue.name }}</text>
                                    <u-icon name="arrow-right" size="20" color="#C0C4CC" />
                                </view>
                            </view>
                            <view
                                class="flex items-center gap-[12rpx] px-[28rpx] h-[100rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <text class="flex-1 text-[28rpx] font-semibold text-[#0D1117]">素材视频原声</text>
                                <u-switch v-model="formData.extra.soundSwitch" inactive-color="#E5E5E5" :size="36" />
                            </view>
                            <navigator
                                :url="`/ai_modules/digital_human/pages/music_choose/music_choose?music=${JSON.stringify(
                                    formData.music,
                                )}&volume=${formData.extra.volume}&ai_music=${formData.extra.ai_music}`"
                                hover-class="none"
                                class="flex items-center gap-[12rpx] px-[28rpx] h-[100rpx]">
                                <text class="flex-1 text-[28rpx] font-semibold text-[#0D1117]">背景音乐</text>
                                <view class="flex items-center gap-[4rpx]">
                                    <template v-if="formData.music.length > 0">
                                        <text class="text-[#9CA3AF]">共</text>
                                        <text class="text-primary font-bold">{{ formData.music.length }}</text>
                                        <text class="text-[#9CA3AF]">个</text>
                                    </template>
                                    <text v-else-if="formData.extra.ai_music" class="text-[#9CA3AF]">AI 音乐库</text>
                                    <text v-else class="text-[#9CA3AF]">无</text>
                                    <u-icon name="arrow-right" size="20" color="#C0C4CC" />
                                </view>
                            </navigator>
                        </view>
                        <view
                            class="bg-white rounded-[28rpx] px-[28rpx] py-[24rpx] flex items-center justify-between shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <view>
                                <text class="text-[28rpx] font-bold text-[#0D1117] block mb-[6rpx]">生成视频数量</text>
                                <text class="text-xs text-[#9CA3AF]">每条文案生成的视频数量</text>
                            </view>
                            <view class="flex items-center gap-[16rpx]">
                                <view
                                    class="w-[56rpx] h-[56rpx] rounded-full border border-solid border-[#E5E9F0] bg-white flex items-center justify-center"
                                    @click="handleVideoCount('minus')">
                                    <text class="text-[32rpx] text-primary font-bold leading-none">−</text>
                                </view>
                                <view
                                    class="max-w-[100rpx] h-[56rpx] bg-[#EBF2FF] rounded-[14rpx] flex items-center justify-center">
                                    <u-input
                                        v-model="formData.extra.video_count"
                                        type="digit"
                                        placeholder=""
                                        :custom-style="{
                                            color: '#0065fb',
                                            fontWeight: '800',
                                            fontSize: '30rpx',
                                        }"
                                        input-align="center" />
                                </view>
                                <view
                                    class="w-[56rpx] h-[56rpx] rounded-full border border-solid border-[#E5E9F0] bg-white flex items-center justify-center"
                                    @click="handleVideoCount('add')">
                                    <text class="text-[32rpx] text-primary font-bold leading-none">＋</text>
                                </view>
                            </view>
                        </view>
                        <view
                            class="bg-white rounded-[28rpx] overflow-hidden shadow-[0_2rpx_12rpx_rgba(0,0,0,0.06),0_0_0_1rpx_rgba(0,0,0,0.04)]">
                            <view class="flex items-center gap-[12rpx] px-[28rpx] py-[22rpx] border-b border-[#F0F2F5]">
                                <text class="text-[28rpx] font-bold text-[#0D1117]">使用设置</text>
                            </view>
                            <view
                                class="flex items-center justify-between px-[28rpx] h-[104rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <text class="text-[28rpx] font-semibold text-[#0D1117]">数字人使用</text>
                                <view class="flex bg-[#F0F2F5] rounded-[16rpx] p-[4rpx] w-[240rpx]">
                                    <view
                                        v-for="(item, index) in ORDER_MODE_TABS"
                                        :key="index"
                                        class="flex-1 h-[56rpx] rounded-[12rpx] flex items-center justify-center text-[22rpx] font-semibold transition-all duration-200"
                                        :class="
                                            index === formData.extra.human
                                                ? 'bg-white text-primary shadow-[0_2rpx_6rpx_rgba(0,0,0,0.08)]'
                                                : 'text-[#9CA3AF]'
                                        "
                                        @click="formData.extra.human = index"
                                        >{{ item }}</view
                                    >
                                </view>
                            </view>
                            <view
                                class="flex items-center justify-between px-[28rpx] h-[104rpx] border-[0] border-b border-solid border-[#F0F2F5]">
                                <text class="text-[28rpx] font-semibold text-[#0D1117]">背景音乐使用</text>
                                <view class="flex bg-[#F0F2F5] rounded-[16rpx] p-[4rpx] w-[240rpx]">
                                    <view
                                        v-for="(item, index) in ORDER_MODE_TABS"
                                        :key="index"
                                        class="flex-1 h-[56rpx] rounded-[12rpx] flex items-center justify-center text-[22rpx] font-semibold transition-all duration-200"
                                        :class="
                                            index === formData.extra.music
                                                ? 'bg-white text-primary shadow-[0_2rpx_6rpx_rgba(0,0,0,0.08)]'
                                                : 'text-[#9CA3AF]'
                                        "
                                        @click="formData.extra.music = index"
                                        >{{ item }}</view
                                    >
                                </view>
                            </view>
                            <view
                                class="flex items-center justify-between px-[28rpx] h-[104rpx]"
                                :class="formData.extra.clip === 1 ? 'border-[0] border-b border-[#F0F2F5]' : ''">
                                <text class="text-[28rpx] font-semibold text-[#0D1117]">视频风格</text>
                                <view class="flex bg-[#F0F2F5] rounded-[16rpx] p-[4rpx] w-[240rpx]">
                                    <view
                                        v-for="(item, index) in CLIP_MODE_TABS"
                                        :key="index"
                                        class="flex-1 h-[56rpx] rounded-[12rpx] flex items-center justify-center text-[22rpx] font-semibold transition-all duration-200"
                                        :class="
                                            index === formData.extra.clip
                                                ? 'bg-white text-primary shadow-[0_2rpx_6rpx_rgba(0,0,0,0.08)]'
                                                : 'text-[#9CA3AF]'
                                        "
                                        @click="formData.extra.clip = index"
                                        >{{ item }}</view
                                    >
                                </view>
                            </view>
                            <navigator
                                v-if="formData.extra.clip === 1"
                                :url="`/ai_modules/digital_human/pages/montage_styles_choose/montage_styles_choose?type=${
                                    MontageStylesType.DIGITAL_PERSON
                                }&data=${JSON.stringify(formData.clip)}`"
                                hover-class="none"
                                class="flex items-center gap-[12rpx] px-[28rpx] h-[100rpx]">
                                <text class="flex-1 text-[28rpx] font-semibold text-[#0D1117]">选择视频风格</text>
                                <view class="flex items-center gap-[4rpx]">
                                    <text class="text-[#9CA3AF]">共</text>
                                    <text class="text-primary font-bold">{{ formData.clip.length }}</text>
                                    <text class="text-[#9CA3AF]">个</text>
                                    <u-icon name="arrow-right" size="13" color="#C0C4CC" />
                                </view>
                            </navigator>
                        </view>
                    </view>
                </scroll-view>
            </view>
        </view>

        <view
            class="flex-shrink-0 bg-white border-[0] border-t border-solid border-[#F0F2F5] px-6 pt-[20rpx] pb-[40rpx] flex items-center gap-[16rpx]">
            <view
                v-if="currentStep === 1"
                class="w-[100rpx] h-[96rpx] rounded-[20rpx] flex flex-col items-center justify-center transition-all duration-300"
                :class="formData.anchorLists.length > 0 ? 'bg-[#EBF2FF]' : 'bg-[#F0F2F5]'">
                <text
                    class="text-[32rpx] font-extrabold leading-none"
                    :class="formData.anchorLists.length > 0 ? 'text-primary' : 'text-[#C0C4CC]'">
                    {{ formData.anchorLists.length }}
                </text>
                <text
                    class="text-[20rpx] mt-[4rpx]"
                    :class="formData.anchorLists.length > 0 ? 'text-[#0065fb]/70' : 'text-[#C0C4CC]'">
                    已选
                </text>
            </view>

            <view
                v-else-if="currentStep < steps.length"
                class="h-[96rpx] px-[40rpx] rounded-[24rpx] flex items-center gap-[8rpx] border border-solid border-[#E5E9F0] bg-white"
                @click="handleStep(currentStep, 'prev')">
                <text class="text-[28rpx] font-semibold text-[#4B5563]">上一步</text>
            </view>

            <view v-else class="flex flex-col items-center gap-[6rpx] px-[16rpx]" @click="showTokensCost = true">
                <image src="@/ai_modules/digital_human/static/icons/star.svg" class="w-[36rpx] h-[36rpx]"></image>
                <text class="text-[20rpx] text-[#9CA3AF] font-medium">算力消耗</text>
            </view>

            <view
                v-if="currentStep < steps.length"
                class="flex-1 h-[96rpx] rounded-[24rpx] flex items-center justify-center gap-[8rpx] transition-all duration-300"
                :class="canNext ? 'bg-primary shadow-[0_8rpx_24rpx_rgba(28,111,235,0.30)]' : 'bg-[#E5E7EB]'"
                @click="handleStep(currentStep, 'next')">
                <text class="text-[30rpx] font-bold" :class="canNext ? 'text-white' : 'text-[#9CA3AF]'">下一步</text>
            </view>

            <view
                v-else
                class="flex-1 h-[100rpx] rounded-[24rpx] flex items-center justify-center gap-[10rpx] relative overflow-hidden shadow-[0_10rpx_30rpx_rgba(28,111,235,0.35)]"
                style="background: linear-gradient(135deg, #0065fb 0%, #0ea5e9 100%)"
                @click="handleCreateVideo">
                <text class="text-[32rpx] font-extrabold text-white tracking-wide">立即生成视频</text>
            </view>
        </view>

        <choose-character v-if="showCharacter" v-model="showCharacter" @select="handleSelectCharacter" />
        <upload-category-panel v-model="showUploadCategoryPanel" @select="handleSelectCategory" />
        <choose-material
            v-model="showMaterialLibrary"
            :limit="uploadMaterialType == 'image' || replaceMaterialIndex === -1 ? 9 : 1"
            :type="uploadMaterialType"
            :mode="uploadMaterialMode"
            @select="handleSelectMaterial" />
        <choose-history
            v-model="showChooseHistory"
            :limit="replaceMaterialIndex === -1 ? 9 : 1"
            @select="handleSelectHistory" />
        <choose-agent v-if="showChooseAgent" v-model="showChooseAgent" @select="handleSelectAgent" />
        <upload-rule-pop
            v-if="showUploadTip"
            v-model="showUploadTip"
            @handle-upload="uploadAndProcessFiles(uploadMaterialType)" />
        <upload-progress v-if="showUploadProgress" v-model="showUploadProgress" :upload-list="uploadMaterialList" />
        <upload-progress
            v-if="showUploadAudioProgress"
            v-model="showUploadAudioProgress"
            :upload-list="uploadAudioMaterialList" />
        <video-preview
            v-if="showVideoPreview"
            v-model="showVideoPreview"
            title="视频预览"
            :poster="videoPreview.poster"
            :video-url="videoPreview.url" />
        <choose-tone
            ref="chooseToneRef"
            v-model="showChooseTone"
            :limit="1"
            :model-version="`${DigitalHumanModelVersionEnum.SHANJIAN},${DigitalHumanModelVersionEnum.MINIMAX_HD},${DigitalHumanModelVersionEnum.MINIMAX_TURBO}`"
            :show-original-tone="true"
            :show-free-tone="false"
            @select="handleSelectTone" />
        <create-success-pop
            v-if="showCreateSuccess"
            v-model="showCreateSuccess"
            title="混剪视频创建成功"
            desc="您可以立即去设置发布任务，也可以等待混剪视频成功后再发布"
            @to="toPublish"
            @seek="toRecord" />
        <recharge-popup ref="rechargePopupRef" />
        <tokens-cost v-if="showTokensCost" v-model="showTokensCost" :type="MontageTypeEnum.REAL_PERSON_MIX" />
        <recorder-control
            v-if="showRecorder"
            v-model="showRecorder"
            ref="recorderRef"
            @close="showRecorder = false"
            @success="recorderSuccess" />
        <choose-audio-type
            v-if="showAudioType"
            v-model="showAudioType"
            @recorder="openRecorder"
            @file="uploadAudio('file')" />
    </view>
</template>

<script setup lang="ts">
import { DigitalHumanModelVersionEnum } from "@/enums/appEnums";
import { ListenerTypeEnum, MontageTypeEnum, MontageStylesType } from "@/ai_modules/digital_human/enums";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import Steps from "@/ai_modules/digital_human/components/steps/steps.vue";
import MaterialDurationBar from "@/ai_modules/digital_human/components/material-duration-bar/material-duration-bar.vue";
import MaterialContainer from "@/ai_modules/digital_human/components/material-container/material-container.vue";
import UploadRulePop from "@/ai_modules/digital_human/components/upload-rule-pop/upload-rule-pop.vue";
import ChooseCharacter from "@/ai_modules/digital_human/components/choose-character/choose-character.vue";
import CreateSuccessPop from "@/ai_modules/digital_human/components/create-success-pop/create-success-pop.vue";
import VideoPreview from "@/components/video-preview/video-preview.vue";
import TokensCost from "@/ai_modules/digital_human/components/tokens-cost/tokens-cost.vue";
import ChooseTone from "@/ai_modules/digital_human/components/choose-tone/choose-tone.vue";
import ChooseAudioType from "@/ai_modules/digital_human/components/choose-audio-type/choose-audio-type.vue";
import ChooseAgent from "@/ai_modules/digital_human/components/choose-agent/choose-agent.vue";
import RecorderControl from "@/ai_modules/digital_human/components/recorder-control/recorder-control.vue";
import CopywriterEmpty from "@/ai_modules/digital_human/components/copywriter-empty/copywriter-empty.vue";
import VideoCoverUpload from "@/ai_modules/digital_human/components/video-cover-upload/video-cover-upload.vue";

// ─── 引入各步骤 Hooks ────────────────────────────────────────────
import { useSteps } from "./hooks/useSteps";
import { useAnchor } from "./hooks/useAnchor";
import { usePersona } from "../../hooks/usePersona";
import { useCopywriter, COPYWRITER_LIMIT, COPYWRITER_TABS } from "../../hooks/useCopywriter";
import { useMaterialStep } from "../../hooks/useMaterialStep";
import { useGenerateSetting, ORDER_MODE_TABS, CLIP_MODE_TABS } from "./hooks/useGenerateSetting";

// ─── 表单数据（跨步骤共享，在页面层统一维护） ────────────────────

const formData = reactive<{
    shanjian_type: MontageTypeEnum;
    anchorLists: any[];
    name: string;
    person_name: string;
    person_introduction: string;
    copywriterList: any[];
    materialList: any[];
    music: any[];
    extra: {
        ai_music: boolean;
        volume: number;
        soundSwitch: boolean;
        human: number;
        music: number;
        clip: number;
        video_count: number;
    };
    voice: any[];
    audio: any[];
    clip: any[];
    cover: string;
}>({
    shanjian_type: MontageTypeEnum.REAL_PERSON_MIX,
    anchorLists: [],
    name: `${uni.$u.timeFormat(Date.now(), "yyyymmddhhMM")}口播混剪`,
    person_name: "",
    person_introduction: "",
    copywriterList: [],
    materialList: [],
    music: [],
    extra: {
        ai_music: true,
        volume: 0.5,
        soundSwitch: false,
        human: 0,
        music: 0,
        clip: 0,
        video_count: 1,
    },
    voice: [],
    audio: [],
    clip: [],
    cover: "",
});

// ─── Refs ────────────────────────────────────────────────────────

const rechargePopupRef = shallowRef();
const recorderRef = shallowRef<InstanceType<typeof RecorderControl>>();
const materialDurationBarRef = ref<InstanceType<typeof MaterialDurationBar>>();
const showChooseAgent = ref(false);
const chooseToneRef = ref<InstanceType<typeof ChooseTone>>();

// ─── Step 4 Hook（需先初始化，getMaterialTotalDuration 供 useSteps 使用） ──

const {
    montageConfig,
    showUploadCategoryPanel,
    showMaterialLibrary,
    showChooseHistory,
    showVideoPreview,
    showUploadTip,
    uploadMaterialType,
    uploadMaterialMode,
    replaceMaterialIndex,
    videoPreview,
    showUploadProgress,
    uploadMaterialList,
    uploadAndProcessFiles,
    getDurationLimit,
    getMaterialTotalDuration,
    handleSelectCategory,
    chooseUploadType,
    handleSelectMaterial,
    handleSelectHistory,
    previewMaterial,
    handleReplaceMaterial,
    handleDeleteMaterial,
} = useMaterialStep({ formData });

// ─── Step 3 Hook ─────────────────────────────────────────────────

const {
    copywriterTypeIndex,
    editCopywriterIndex,
    currentAudioIndex,
    showAudioType,
    showRecorder,
    isPlaying,
    showUploadAudioProgress,
    uploadAudioMaterialList,
    uploadAudio,
    isSingleCopywriterValid,
    handleShowCopywriter,
    handleSelectCopywriter,
    handleSelectAgent,
    handleDeleteCopywriter,
    handlePlayAudio,
    openRecorder,
    recorderSuccess,
    onCopywriterConfirm,
    destroy,
} = useCopywriter({ formData, recorderRef });

// ─── Step 1 Hook ─────────────────────────────────────────────────

const { anchorLists, anchorPagingRef, getAnchorList, handleAnchorSelect, handleCreateAnchor } = useAnchor({
    formData,
});

// ─── Step 2 Hook ─────────────────────────────────────────────────

const { showCharacter, isCharacter, handleSelectCharacter } = usePersona({ formData });

// ─── Step 1 Hook（步骤导航，依赖前面 hooks 的方法） ──────────────

const {
    step: currentStep,
    steps,
    canNext,
    handleStep,
} = useSteps({
    formData,
    copywriterTypeIndex,
    getMaterialTotalDuration,
    isSingleCopywriterValid,
});

// ─── Step 5 Hook ─────────────────────────────────────────────────

const {
    showChooseTone,
    voiceValue,
    showCreateSuccess,
    showTokensCost,
    handleVideoCount,
    openChooseTone,
    handleSelectTone,
    handleCreateVideo,
    toPublish,
    toRecord,
} = useGenerateSetting({
    formData,
    copywriterTypeIndex,
    rechargePopupRef,
    chooseToneRef,
});

// ─── EventBus：统一处理所有子页面回调 ───────────────────────────

const { on } = useEventBusManager();

onLoad(() => {
    on("confirm", (res: any) => {
        const { type, data } = res;

        // Step 1：新增形象后刷新列表
        if (type === ListenerTypeEnum.CREATE_ANCHOR) {
            if (!data) return;
            anchorLists.value = anchorLists.value.concat(data);
        }

        // Step 3：手动输入 / AI 生成文案回填
        if (type === ListenerTypeEnum.MONTAGE_COPYWRITER || type === ListenerTypeEnum.AI_COPYWRITER) {
            onCopywriterConfirm(data);
        }

        // Step 5：背景音乐选择回填
        if (type === ListenerTypeEnum.CHOOSE_MUSIC) {
            if (data.music === -1) {
                formData.extra.ai_music = true;
                formData.music = [];
            } else {
                formData.music = data.music;
                formData.extra.ai_music = false;
            }
            formData.extra.volume = data.volume;
        }

        // Step 5：视频风格选择回填
        if (type === ListenerTypeEnum.CHOOSE_VIDEO_STYLES) {
            if (!data.length) return;
            formData.clip = data;
        }
    });
});

onUnload(() => {
    destroy();
});
</script>

<style scoped lang="scss">
@keyframes shimmer {
    0% {
        transform: translateX(-100%);
    }
    100% {
        transform: translateX(200%);
    }
}
</style>
