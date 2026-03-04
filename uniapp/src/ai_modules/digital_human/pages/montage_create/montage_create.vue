<template>
    <view class="h-screen flex flex-col device-bg">
        <u-navbar
            title="数字人口播混剪"
            title-bold
            :is-fixed="false"
            :border-bottom="false"
            :background="{
                background: 'transparent',
            }">
        </u-navbar>
        <view class="flex-shrink-0 h-[150rpx] flex items-center">
            <view class="grid grid-cols-5 w-full">
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
            <view v-show="step === 1" class="flex flex-col h-full">
                <view class="flex items-center justify-between px-4">
                    <text class="font-medium">选择形象</text>
                    <view class="flex items-center gap-x-1" @click="handleCreateAnchor">
                        <image
                            src="@/ai_modules/digital_human/static/icons/add.svg"
                            class="w-[28rpx] h-[28rpx]"></image>
                        <text>新增形象</text>
                    </view>
                </view>
                <view class="grow min-h-0 mt-[38rpx]">
                    <view class="h-full">
                        <z-paging ref="anchorPagingRef" v-model="anchorLists" :fixed="false" @query="getAnchorList">
                            <view class="grid grid-cols-3 gap-4 px-4 pb-4">
                                <view
                                    v-for="(item, index) in anchorLists"
                                    :key="index"
                                    class="h-[276rpx] rounded-xl relative overflow-hidden"
                                    @click="handleAnchorSelect(item)">
                                    <image
                                        :src="item.pic"
                                        lazy-load
                                        class="w-full h-full rounded-xl"
                                        mode="aspectFill"></image>
                                    <template v-if="item.status === 6">
                                        <view
                                            class="absolute right-2 bottom-2"
                                            @click.stop="previewMaterial({ pic: item.pic, url: item.anchor_url })">
                                            <image src="/static/images/icons/play.svg" class="w-[40rpx] h-[40rpx]" />
                                        </view>
                                        <view
                                            class="absolute top-0 left-0 w-full h-full bg-[#00000080]"
                                            v-if="formData.anchorLists.includes(item)">
                                            <view class="absolute top-2 right-2">
                                                <image
                                                    src="/static/images/icons/success.svg"
                                                    class="w-[28rpx] h-[28rpx]"></image>
                                            </view>
                                        </view>
                                        <view
                                            class="absolute top-2 right-2 w-[28rpx] h-[28rpx] rounded-full bg-white"
                                            v-else></view>
                                    </template>
                                    <view
                                        v-else
                                        class="w-full h-full flex flex-col items-center justify-center absolute top-0 left-0 z-[22] bg-[#0000005E]">
                                        <view
                                            class="w-6 h-6 flex items-center justify-center rounded-full bg-primary mb-2">
                                            <image
                                                src="@/ai_modules/digital_human/static/icons/pic2.svg"
                                                class="w-[28rpx] h-[28rpx]"></image>
                                        </view>
                                        <view class="text-[22rpx] text-white">正在生成中</view>
                                    </view>
                                </view>
                            </view>
                            <template #empty>
                                <view class="h-full flex flex-col items-center justify-center">
                                    <image
                                        src="@/ai_modules/digital_human/static/images/common/avatar.png"
                                        class="w-[120rpx] h-[136rpx] mx-auto"></image>
                                    <view class="text-[26rpx] text-[#828282] mt-[32rpx] text-center">
                                        您还没有数字人，快去定制一个吧~
                                    </view>
                                    <view
                                        class="mt-[28rpx] mx-auto w-[202rpx] h-[68rpx] flex items-center justify-center rounded-[12rpx] text-white bg-black"
                                        @click="handleCreateAnchor">
                                        定制数字人
                                    </view>
                                </view>
                            </template>
                        </z-paging>
                    </view>
                </view>
            </view>
            <view
                v-show="step === 2"
                class="bg-white rounded-[16rpx] px-4 py-[28rpx] shadow-[0rpx_6rpx_12rpx_0_rgba(0,0,0,0.03)] mx-4">
                <text class="font-medium">身份人设</text>
                <view class="mt-[28rpx]">
                    <view class="text-[#7C7E80]">人设名称</view>
                    <view class="mt-[12rpx]">
                        <view class="border-[0] border-b-[1rpx] border-solid border-[#EDEDED] py-1">
                            <u-input
                                v-model="formData.person_name"
                                placeholder-style="font-size: 24rpx;"
                                placeholder="请输入人设名称"
                                maxlength="20"
                                type="textarea"
                                height="30"
                                @change="isCharacter = false" />
                        </view>
                    </view>
                </view>
                <view class="mt-[28rpx]">
                    <view class="text-[#7C7E80]">人设介绍</view>
                    <view class="mt-[12rpx]">
                        <view class="border-[0] border-b-[1rpx] border-solid border-[#EDEDED] py-1">
                            <u-input
                                v-model="formData.person_introduction"
                                placeholder-style="font-size: 24rpx;"
                                placeholder="请输入人设介绍"
                                maxlength="50"
                                type="textarea"
                                height="30"
                                @change="isCharacter = false" />
                        </view>
                    </view>
                </view>
                <view class="mt-[48rpx] flex justify-end">
                    <view
                        class="flex items-center gap-x-1 bg-[#F1F1F1] px-2 py-1 rounded-[8rpx]"
                        @click="showCharacter = true">
                        <image
                            src="@/ai_modules/digital_human/static/icons/user2.svg"
                            class="w-[24rpx] h-[24rpx]"></image>
                        <text class="text-xs">历史人设</text>
                    </view>
                </view>
            </view>
            <view v-show="step === 3" class="h-full flex flex-col">
                <view class="flex justify-center mb-3">
                    <view class="bg-white rounded-[16rpx] px-[8rpx]">
                        <view class="w-[360rpx] grid grid-cols-2 relative h-[80rpx]">
                            <view
                                v-for="(item, index) in ['选择文案', '选择音频']"
                                :key="index"
                                class="type-item"
                                :class="{ active: copywriterTypeIndex === index }"
                                @click="copywriterTypeIndex = index">
                                {{ item }}
                            </view>
                            <view
                                class="tab-slider !bg-[#0065fb]/5"
                                :style="{ transform: `translateX(${copywriterTypeIndex * 100}%)` }"></view>
                        </view>
                    </view>
                </view>
                <view class="flex items-center gap-x-2 px-4">
                    <template v-if="copywriterTypeIndex === 0">
                        <view
                            class="flex-1 flex items-center justify-center gap-x-2 bg-white h-[100rpx] rounded-[10rpx]"
                            @click="handleShowCopywriter()">
                            <image src="/static/images/icons/edit.svg" class="w-[32rpx] h-[32rpx]"></image>
                            <text class="font-medium text-[32rpx]">手动输入</text>
                        </view>
                        <view
                            class="flex-1 h-[100rpx] flex items-center justify-center gap-x-2 bg-black rounded-[10rpx]"
                            @click="showChooseAgent = true">
                            <image src="/static/images/common/magic_white.png" class="w-[32rpx] h-[32rpx]"></image>
                            <text class="text-white font-medium text-[32rpx]">AI生成</text>
                        </view>
                    </template>
                    <view
                        v-if="copywriterTypeIndex === 1"
                        class="bg-white rounded-[10rpx] w-full h-[100rpx] flex items-center justify-center gap-x-2"
                        @click="showAudioType = true">
                        <u-icon name="plus" size="20"></u-icon>
                        <text class="font-medium text-[32rpx]">添加音频</text>
                    </view>
                </view>
                <view class="grow min-h-0 mt-4">
                    <scroll-view scroll-y class="h-full">
                        <view class="px-4 flex flex-col gap-4 pb-[350rpx]">
                            <template v-if="copywriterTypeIndex === 0">
                                <view
                                    v-for="(item, index) in formData.copywriterList"
                                    :key="index"
                                    class="copywriter-item"
                                    @click="handleSelectCopywriter(index)">
                                    <view class="text-[32rpx] font-medium mr-4">
                                        {{ item.title }}
                                    </view>
                                    <view class="mt-[28rpx]">
                                        {{ item.content }}
                                    </view>
                                    <view
                                        class="absolute right-2 top-2 rounded-full flex item-center justify-center w-4 h-4 bg-[#0000004C]"
                                        @click.stop="handleDeleteCopywriter(index)">
                                        <u-icon name="close" color="#ffffff" size="16"></u-icon>
                                    </view>
                                </view>
                            </template>
                            <template v-if="copywriterTypeIndex === 1">
                                <view v-for="(item, index) in formData.audio" :key="index" class="copywriter-item">
                                    <view class="flex items-center gap-x-2">
                                        <view @click="handlePlayAudio(item.url, index)">
                                            <u-icon
                                                :name="
                                                    isPlaying && currentAudioIndex === index
                                                        ? 'pause-circle'
                                                        : 'play-circle'
                                                "
                                                color="#0065fb"
                                                size="50"></u-icon>
                                        </view>
                                        <text class="font-medium text-[30rpx]">录制的音频</text>
                                    </view>
                                    <view class="mt-[40rpx] pb-3">
                                        <u-input
                                            v-model="item.content"
                                            type="textarea"
                                            placeholder="请输入音频内容"
                                            maxlength="500"
                                            height="250" />
                                    </view>
                                    <view
                                        class="absolute right-2 top-2 rounded-full flex item-center justify-center w-4 h-4 bg-[#0000004C]"
                                        @click.stop="handleDeleteCopywriter(index)">
                                        <u-icon name="close" color="#ffffff" size="16"></u-icon>
                                    </view>
                                </view>
                            </template>
                        </view>
                    </scroll-view>
                </view>
            </view>
            <view v-show="step === 4" class="h-full flex flex-col">
                <view class="mx-4">
                    <text class="font-medium">混剪素材（共{{ formData.materialList.length }}个）</text>
                    <view class="mt-1 text-xs text-[#0000004d]">
                        总量限制：全部素材总时长不得超过{{ montageConfig.materialTotalDuration }}分钟 (图片按{{
                            montageConfig.imageDuration
                        }}秒/张，视频按实际时长/个)</view
                    >
                </view>
                <view class="grow min-h-0">
                    <scroll-view scroll-y class="h-full">
                        <view class="grid grid-cols-3 gap-[26rpx] p-4">
                            <view v-for="(item, index) in formData.materialList" :key="index" class="relative">
                                <view
                                    class="h-[220rpx] rounded-[12rpx] relative overflow-hidden"
                                    @click="previewMaterial(item)">
                                    <image
                                        :src="item.pic"
                                        class="w-full h-full rounded-[12rpx]"
                                        mode="aspectFill"></image>
                                    <view
                                        class="absolute bottom-0 h-[40rpx] w-full bg-[rgba(0,0,0,0.5)] flex items-center justify-center z-[88]">
                                        <image
                                            v-if="item.type === 'image'"
                                            src="@/ai_modules/digital_human/static/icons/pic.svg"
                                            class="w-[24rpx] h-[24rpx]"></image>
                                        <image
                                            v-else
                                            src="@/ai_modules/digital_human/static/icons/video.svg"
                                            class="w-[24rpx] h-[24rpx]"></image>
                                    </view>
                                    <view class="absolute bottom-4 w-full z-[89] flex justify-center">
                                        <view class="dh-version-name" @click.stop="handleReplaceMaterial(index)">
                                            替换
                                        </view>
                                    </view>
                                </view>
                                <view
                                    class="absolute -top-2 -right-2 z-[77] rounded-full bg-[#0000004C] w-[32rpx] h-[32rpx] flex items-center justify-center"
                                    @click="handleDeleteMaterial(index)">
                                    <u-icon name="close" color="#ffffff" size="16"></u-icon>
                                </view>
                            </view>
                            <view
                                class="bg-white rounded-[12rpx] flex flex-col items-center justify-center h-[220rpx]"
                                @click="chooseUploadType">
                                <image
                                    src="@/ai_modules/digital_human/static/icons/add.svg"
                                    class="w-[40rpx] h-[40rpx]"></image>
                                <text class="text-xs text-[#4E5158] mt-[24rpx]">添加素材</text>
                            </view>
                        </view>
                    </scroll-view>
                </view>
            </view>
            <scroll-view scroll-y class="h-full" v-show="step === 5">
                <view class="px-4 pb-[150rpx]">
                    <view>
                        <view class="text-[30rpx] font-medium">视频名称</view>
                        <view class="mt-[20rpx] bg-white rounded-[20rpx] px-4 h-[100rpx] flex items-center">
                            <u-input
                                v-model="formData.name"
                                maxlength="50"
                                placeholder-style="font-size:26rpx;"
                                placeholder="请输入" />
                        </view>
                    </view>
                    <view class="mt-[20rpx] bg-white rounded-[20rpx] px-4">
                        <view class="flex items-center justify-between py-2.5">
                            <view class="text-[30rpx] font-medium">数字人形象</view>
                            <view class="flex items-center gap-x-1" @click="handleStep(1)">
                                <view>
                                    共<text class="mx-1 text-primary font-medium">{{
                                        formData.anchorLists.length
                                    }}</text
                                    >个
                                </view>
                                <u-icon name="arrow-right" :size="20" color="#B2B2B2"></u-icon>
                            </view>
                        </view>
                        <scroll-view scroll-x class="mt-1">
                            <view class="flex gap-x-[24rpx] pb-2">
                                <view
                                    v-for="(item, index) in formData.anchorLists"
                                    :key="index"
                                    class="flex-shrink-0 w-[167rpx] h-[224rpx] rounded-[24rpx]">
                                    <image
                                        :src="item.pic"
                                        class="w-full h-full rounded-[24rpx]"
                                        mode="aspectFill"></image>
                                </view>
                            </view>
                        </scroll-view>
                    </view>
                    <view class="mt-[20rpx] bg-white rounded-[20rpx] px-4">
                        <view
                            class="flex items-center justify-between h-[106rpx] border-[0] border-b-[1rpx] border-solid border-[#00000008]">
                            <view class="text-[30rpx] font-medium">身份人设</view>
                            <view class="flex items-center">
                                <view
                                    class="line-clamp-1 min-w-[150rpx] text-end"
                                    :class="{ 'text-primary': formData.person_name }"
                                    >{{ formData.person_name || "无" }}</view
                                >
                                <view class="w-[1rpx] h-[24rpx] bg-[#C6CACC] mx-2"></view>
                                <view class="line-clamp-1" :class="{ 'text-primary': formData.person_introduction }">
                                    {{ formData.person_introduction || "无" }}
                                </view>
                                <u-icon name="arrow-right" :size="20" color="#C5CACA"></u-icon>
                            </view>
                        </view>
                        <view
                            class="flex items-center justify-between h-[106rpx] border-[0] border-b-[1rpx] border-solid border-[#00000008]">
                            <view class="text-[30rpx] font-medium">口播文案</view>
                            <view class="flex items-center gap-x-1" @click="handleStep(3)">
                                <view>
                                    共<text class="mx-1 text-primary font-medium">{{
                                        formData.copywriterList.length || formData.audio.length
                                    }}</text
                                    >个
                                </view>
                                <u-icon name="arrow-right" :size="20" color="#B2B2B2"></u-icon>
                            </view>
                        </view>
                        <view
                            class="flex items-center justify-between h-[106rpx] border-[0] border-b-[1rpx] border-solid border-[#00000008]">
                            <view class="text-[30rpx] font-medium">参考素材</view>
                            <view class="flex items-center gap-x-1" @click="handleStep(4)">
                                <view>
                                    共<text class="mx-1 text-primary font-medium">{{
                                        formData.materialList.length
                                    }}</text
                                    >个
                                </view>
                                <u-icon name="arrow-right" :size="20" color="#B2B2B2"></u-icon>
                            </view>
                        </view>

                        <view
                            class="flex items-center justify-between h-[106rpx] border-[0] border-b-[1rpx] border-solid border-[#00000008]">
                            <view class="text-[30rpx] font-medium">选择音色</view>
                            <view class="flex items-center gap-x-1" @click="showChooseTone = true">
                                <view
                                    v-if="!voiceValue.name"
                                    class="text-[20rpx] text-primary bg-[#DDF3FF] rounded font-medium p-1">
                                    视频原音
                                </view>
                                <view v-else class="text-primary font-medium">
                                    {{ voiceValue.name }}
                                </view>
                                <u-icon name="arrow-right" :size="20" color="#B2B2B2"></u-icon>
                            </view>
                        </view>
                        <view
                            class="flex items-center justify-between h-[106rpx] border-[0] border-b-[1rpx] border-solid border-[#00000008]">
                            <view class="text-[30rpx] font-medium">素材视频原声</view>
                            <u-switch v-model="formData.extra.soundSwitch" inactive-color="#E5E5E5" :size="40" />
                        </view>
                        <view class="flex items-center justify-between h-[106rpx]">
                            <view class="text-[30rpx] font-medium">背景音乐</view>
                            <navigator
                                :url="`/ai_modules/digital_human/pages/music_choose/music_choose?music=${JSON.stringify(
                                    formData.music
                                )}&volume=${formData.extra.volume}`"
                                hover-class="none"
                                class="flex items-center gap-x-1">
                                <view>
                                    <template v-if="formData.music.length > 0">
                                        共<text class="mx-1 text-primary font-medium">{{ formData.music.length }}</text
                                        >个
                                    </template>
                                    <text class="text-[#000000]/70" v-else>AI音乐库</text>
                                </view>
                                <u-icon name="arrow-right" :size="20" color="#B2B2B2"></u-icon>
                            </navigator>
                        </view>
                    </view>
                    <view class="flex items-center justify-between bg-white mt-[22rpx] p-4 rounded-[20rpx]">
                        <view>
                            <view class="text-[30rpx] font-medium">生成视频数量</view>
                            <view class="text-[#000000]/50"> 每条文案生成视频的数量 </view>
                        </view>
                        <view class="flex items-center gap-x-2">
                            <view class="p-[4rpx] leading-[0]" @click="handleMinusVideoCount('minus')">
                                <image
                                    src="@/ai_modules/digital_human/static/icons/minus_circle.svg"
                                    class="w-[36rpx] h-[36rpx]"></image>
                            </view>
                            <view
                                class="w-[90rpx] h-[52rpx] px-1 flex items-center justify-center bg-[#F6F6F6] rounded-[10rpx]">
                                <u-input
                                    v-model="formData.extra.video_count"
                                    type="digit"
                                    placeholder=""
                                    :min="1"
                                    :max="99"
                                    :custom-style="{ color: '#0065fb', fontWeight: 'bold' }"
                                    input-align="center" />
                            </view>
                            <view class="p-[4rpx] leading-[0]" @click="handleMinusVideoCount('add')">
                                <image
                                    src="@/ai_modules/digital_human/static/icons/add_circle.svg"
                                    class="w-[36rpx] h-[36rpx]"></image>
                            </view>
                        </view>
                    </view>
                    <view class="mt-[22rpx] bg-white rounded-[20rpx] px-4">
                        <view class="font-medium text-[30rpx] py-3">使用设置</view>
                        <view
                            class="flex items-center justify-between border-[0] border-b-[1rpx] border-solid border-[#00000008]">
                            <view
                                class="flex items-center justify-between h-[106rpx] border-[0] border-b-[1rpx] border-solid border-[#00000008]">
                                <view class="font-medium">数字人使用</view>
                            </view>
                            <view class="bg-[#F3F4FB] rounded-[16rpx] px-[4rpx] w-[268rpx]">
                                <view class="grid grid-cols-2 gap-x-1 h-[68rpx] relative">
                                    <view
                                        v-for="(item, index) in ['按顺序', '随机']"
                                        :key="index"
                                        class="type-item"
                                        :class="{ active: index == formData.extra.human }"
                                        @click="formData.extra.human = index">
                                        {{ item }}
                                    </view>
                                    <view
                                        class="tab-slider"
                                        :style="{
                                            transform: `translateX(${formData.extra.human * 100}%)`,
                                        }"></view>
                                </view>
                            </view>
                        </view>
                        <view
                            class="flex items-center justify-between border-[0] border-b-[1rpx] border-solid border-[#00000008]">
                            <view class="flex items-center justify-between h-[106rpx]">
                                <view class="font-medium">背景音乐使用</view>
                            </view>
                            <view class="bg-[#F3F4FB] rounded-[16rpx] px-[4rpx] w-[268rpx]">
                                <view class="grid grid-cols-2 gap-x-1 h-[68rpx] relative">
                                    <view
                                        v-for="(item, index) in ['按顺序', '随机']"
                                        :key="index"
                                        class="type-item"
                                        :class="{ active: index == formData.extra.music }"
                                        @click="formData.extra.music = index">
                                        {{ item }}
                                    </view>
                                    <view
                                        class="tab-slider"
                                        :style="{
                                            transform: `translateX(${formData.extra.music * 100}%)`,
                                        }"></view>
                                </view>
                            </view>
                        </view>
                        <view
                            class="flex items-center justify-between border-[0] border-b-[1rpx] border-solid border-[#00000008]">
                            <view
                                class="flex items-center justify-between h-[106rpx] border-[0] border-b-[1rpx] border-solid border-[#00000008]">
                                <view class="font-medium">视频风格</view>
                            </view>
                            <view class="bg-[#F3F4FB] rounded-[16rpx] px-[4rpx] w-[268rpx]">
                                <view class="grid grid-cols-2 gap-x-1 h-[68rpx] relative">
                                    <view
                                        v-for="(item, index) in ['随机', '手动选择']"
                                        :key="index"
                                        class="type-item"
                                        :class="{ active: index == formData.extra.clip }"
                                        @click="formData.extra.clip = index">
                                        {{ item }}
                                    </view>
                                    <view
                                        class="tab-slider"
                                        :style="{
                                            transform: `translateX(${formData.extra.clip * 100}%)`,
                                        }"></view>
                                </view>
                            </view>
                        </view>
                        <navigator
                            v-if="formData.extra.clip === 1"
                            :url="`/ai_modules/digital_human/pages/montage_styles_choose/montage_styles_choose?type=${
                                MontageStylesType.DIGITAL_PERSON
                            }&data=${JSON.stringify(formData.clip)}`"
                            hover-class="none"
                            class="flex items-center justify-between h-[106rpx]">
                            <view class="text-[30rpx] font-medium">选择视频风格</view>
                            <view class="flex items-center">
                                <view>
                                    共<text class="mx-1 text-primary font-medium">{{ formData.clip.length }}</text
                                    >个
                                </view>
                                <u-icon name="arrow-right" :size="20" color="#C5CACA"></u-icon>
                            </view>
                        </navigator>
                    </view>
                </view>
            </scroll-view>
        </view>
        <view class="bg-white shadow-[0_0_0_1rpx_rgba(0,0,0,0.05)] flex-shrink-0 pb-5">
            <view class="flex items-center justify-between px-4 h-[140rpx]">
                <template v-if="step != steps.length">
                    <view
                        v-if="step === 1"
                        class="w-[100rpx] h-[100rpx] flex flex-col items-center justify-center rounded-md text-white"
                        :class="[formData.anchorLists.length > 0 ? 'bg-black' : 'bg-[#787878CC]']">
                        <text class="font-medium text-[32rpx]">{{ formData.anchorLists.length }}</text>
                        <text class="text-xs mt-1">已选</text>
                    </view>
                    <view v-else>
                        <view
                            class="px-[48rpx] py-[20rpx] rounded-md border border-solid border-[#F1F2F5] text-[#878787]"
                            @click="handleStep(step, 'prev')">
                            上一步
                        </view>
                    </view>
                    <view
                        class="px-[48rpx] py-[20rpx] rounded-md text-white"
                        :class="[canNext ? 'bg-black' : 'bg-[#787878CC]']"
                        @click="handleStep(step, 'next')">
                        下一步
                    </view>
                </template>
                <template v-else>
                    <view class="flex flex-col items-center gap-y-2" @click="showTokensCost = true">
                        <image
                            src="@/ai_modules/digital_human/static/icons/star.svg"
                            class="w-[36rpx] h-[36rpx]"></image>
                        <text class="text-[#8C8C8C] text-[22rpx]">算力消耗</text>
                    </view>
                    <view
                        class="rounded-[16rpx] w-[456rpx] h-[100rpx] bg-black text-white font-medium flex items-center justify-center shadow-[0_12rpx_24rpx_0_rgba(0,0,0,0.12)]"
                        @click="handleCreateVideo">
                        生成视频
                    </view>
                </template>
            </view>
        </view>
    </view>
    <choose-character v-if="showCharacter" v-model="showCharacter" @select="handleSelectCharacter" />
    <choose-material
        v-model="showMaterialLibrary"
        :limit="uploadMaterialType == 'image' ? 9 : 1"
        :type="uploadMaterialType"
        @select="handleSelectMaterial" />
    <choose-history v-model="showChooseHistory" :limit="1" @select="handleSelectHistory"></choose-history>
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
        v-if="showChooseTone"
        v-model="showChooseTone"
        :model-version="DigitalHumanModelVersionEnum.SHANJIAN"
        :active-tone="formData.voice?.[0]?.voice_id"
        :show-free-tone="false"
        @confirm="handleSelectTone" />
    <create-success-pop
        v-if="showCreateSuccess"
        v-model="showCreateSuccess"
        title="混剪视频创建成功"
        desc="您可以立即去设置发布任务，也可以等待混剪视频成功后再发布"
        @to="toPublish"
        @seek="toRecord" />
    <recharge-popup ref="rechargePopupRef"></recharge-popup>
    <tokens-cost v-if="showTokensCost" v-model="showTokensCost" :type="1" />
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
</template>

<script setup lang="ts">
import WechatOA from "@/utils/wechat";
import { getShanjianAnchorList, createShanjianTask, addShanjianPerson } from "@/api/digital_human";
import { lpSceneSpeechToText } from "@/api/ladder_player";
import { useUserStore } from "@/stores/user";
import { DigitalHumanModelVersionEnum } from "@/enums/appEnums";
import { ListenerTypeEnum, MontageTypeEnum, MontageStylesType } from "@/ai_modules/digital_human/enums";
import useUpload from "@/hooks/useUpload";
import { montageConfig } from "@/ai_modules/digital_human/config";
import { useEventBusManager } from "@/hooks/useEventBusManager";
import { useAudio } from "@/hooks/useAudio";
import { useMaterial } from "@/ai_modules/digital_human/hooks/useMaterial";
import UploadRulePop from "@/ai_modules/digital_human/components/upload-rule-pop/upload-rule-pop.vue";
import ChooseCharacter from "@/ai_modules/digital_human/components/choose-character/choose-character.vue";
import ChooseHistory from "@/ai_modules/digital_human/components/choose-history/choose-history.vue";
import CreateSuccessPop from "@/ai_modules/digital_human/components/create-success-pop/create-success-pop.vue";
import VideoPreview from "@/components/video-preview/video-preview.vue";
import TokensCost from "@/ai_modules/digital_human/components/tokens-cost/tokens-cost.vue";
import ChooseTone from "@/ai_modules/digital_human/components/choose-tone/choose-tone.vue";
import ChooseAudioType from "@/ai_modules/digital_human/components/choose-audio-type/choose-audio-type.vue";
import ChooseAgent from "@/ai_modules/digital_human/components/choose-agent/choose-agent.vue";
import RecorderControl from "@/ai_modules/digital_human/components/recorder-control/recorder-control.vue";
const { on } = useEventBusManager();

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

const steps = ref([
    { step: 1, title: "选择形象" },
    { step: 2, title: "填写身份" },
    { step: 3, title: "选择文案" },
    { step: 4, title: "参考素材" },
    { step: 5, title: "生成设置" },
]);

const step = ref(1);

const formData = reactive<{
    shanjian_type: MontageTypeEnum;
    anchorLists: any[];
    name: string;
    copywriterList: any[];
    materialList: any[];
    person_name: string;
    person_introduction: string;
    music: any[];
    extra: {
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
}>({
    shanjian_type: MontageTypeEnum.REAL_PERSON_MIX,
    anchorLists: [],
    name: uni.$u.timeFormat(Date.now(), "yyyymmddhhMM") + "口播混剪",
    person_name: "",
    person_introduction: "",
    copywriterList: [],
    materialList: [],
    music: [],
    extra: {
        volume: 0.5,
        soundSwitch: true,
        human: 0,
        music: 0,
        clip: 0,
        video_count: 1,
    },
    voice: [],
    audio: [],
    clip: [],
});

const anchorLists = ref<any[]>([]);
const anchorPagingRef = ref();
const showCharacter = ref(false);
const isCharacter = ref(false);
const editCopywriterIndex = ref(-1);
const showChooseHistory = ref(false);
const showMaterialLibrary = ref(false);
const uploadMaterialType = ref<any>();
const replaceMaterialIndex = ref(-1);
const copywriterTypeIndex = ref(0);
const showAudioType = ref(false);
const showRecorder = ref(false);
const currentAudioIndex = ref(-1);
const showUploadTip = ref(false);
const isFirstOpen = ref(true);
const showVideoPreview = ref(false);
const videoPreview = reactive({
    poster: "",
    url: "",
});
const showChooseTone = ref(false);
const voiceValue = ref<any>({});
const showChooseAgent = ref(false);

const showCreateSuccess = ref(false);
const createResult = ref<any>(null);
const showTokensCost = ref(false);
const rechargePopupRef = shallowRef();
const recorderRef = shallowRef<InstanceType<typeof RecorderControl>>();

const canStepProceed = (stepNumber: number) => {
    const strategy: Record<number, () => boolean> = {
        1: () => formData.anchorLists.length > 0,
        2: () => {
            if (!formData.person_introduction && !formData.person_name) {
                return true;
            }
            return !!formData.person_introduction.trim() && !!formData.person_name.trim();
        },

        3: () => {
            if (copywriterTypeIndex.value === 0) {
                return (
                    formData.copywriterList.length > 0 &&
                    !formData.copywriterList.some(
                        (item: any) => item.content.trim().length < 3 || item.content.length > 500
                    )
                );
            } else {
                return formData.audio.length > 0;
            }
        },
        4: () => {
            if (formData.materialList.length === 0) return true;
            const totalDuration = getMaterialTotalDuration();
            return totalDuration <= montageConfig.materialTotalDuration * 60;
        },
        5: () => true,
    };
    return strategy[stepNumber]?.() ?? false;
};

const getMaterialTotalDuration = () => {
    return (
        formData.materialList.reduce((acc, item) => (item.type === "video" ? acc + item.duration : acc), 0) +
        formData.materialList.filter((item: any) => item.type === "image").length * montageConfig.imageDuration
    );
};

const canNext = computed(() => canStepProceed(step.value));

const handleStep = (targetStep: number, type?: "next" | "prev") => {
    if (type === "prev") {
        step.value--;
        return;
    }

    if (type === "next") {
        if (canNext.value) {
            step.value++;
        } else {
            const messages: Record<number, () => string> = {
                1: () => "请至少选择一个形象",
                2: () => "填写完整的人设信息",
                3: () => {
                    if (copywriterTypeIndex.value === 0) {
                        if (
                            formData.copywriterList.some(
                                (item: any) => item.content.trim().length < 3 || item.content.length > 500
                            )
                        ) {
                            return "口播文案包含内容不能少于3个字，不能超过500个字";
                        }
                        return "请至少添加一条文案";
                    } else {
                        return "请至少添加一条音频";
                    }
                },
                4: () => {
                    const totalDuration = getMaterialTotalDuration();
                    if (totalDuration > montageConfig.materialTotalDuration * 60)
                        return `素材总时长不能超过${montageConfig.materialTotalDuration}分钟`;
                    return "";
                },
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
    destroy();
};

const { processAndAppend } = useMaterial(toRef(formData, "materialList"));

const handleAnchorSelect = (val: any) => {
    if (val.status === 0) {
        uni.$u.toast("该形象正在生成中，请稍后再选择");
        return;
    }
    if (formData.anchorLists.includes(val)) {
        formData.anchorLists = formData.anchorLists.filter((item: any) => item !== val);
    } else {
        formData.anchorLists.push(val);
    }
    formData.voice = formData.anchorLists.map((item: any) => ({
        voice_id: item.voice_id,
        voice_url: item.voice_url,
        name: item.name,
    }));
};

const handleCreateAnchor = () => {
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/anchor_create/anchor_create",
        params: {
            source: DigitalHumanModelVersionEnum.SHANJIAN,
        },
    });
};

const handleSelectCharacter = (item: any) => {
    formData.person_name = item.name;
    formData.person_introduction = item.introduced;
    isCharacter.value = true;
    showCharacter.value = false;
};

const handleSelectCopywriter = (index: number) => {
    editCopywriterIndex.value = index;
    const selectedCopywriter = formData.copywriterList[index];
    handleShowCopywriter(selectedCopywriter);
};

const handleShowCopywriter = (data?: any) => {
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/montage_copywriter/montage_copywriter",
        params: {
            data: data ? JSON.stringify(data) : "",
        },
    });
};

const handleSelectAgent = (res: any) => {
    const { data } = res;
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/montage_ai_copywriter/montage_ai_copywriter",
        params: {
            agentData: JSON.stringify(data),
        },
    });
};

const handleDeleteCopywriter = (index: number) => {
    if (copywriterTypeIndex.value === 0) {
        formData.copywriterList.splice(index, 1);
    } else {
        formData.audio.splice(index, 1);
    }
};

const { play, pause, isPlaying, destroy } = useAudio();

const handlePlayAudio = (url: string, index: number) => {
    currentAudioIndex.value = index;
    if (isPlaying.value) {
        pause();
    } else {
        play(url);
    }
};

const {
    uploadAndProcessFiles: uploadAudio,
    showUploadProgress: showUploadAudioProgress,
    uploadMaterialList: uploadAudioMaterialList,
} = useUpload({
    count: 1,
    fileAccept: ["mp3", "wav", "m4a", "MP3", "WAV", "M4A"],
    fileSize: 100,
    onSuccess: async (res: any) => {
        const { url } = res[0];
        uni.showLoading({
            title: "正在识别音频",
            mask: true,
        });
        try {
            const { message, audio_duration } = await lpSceneSpeechToText({
                audio: url,
            });
            formData.audio.push({
                content: message,
                url,
                duration: audio_duration,
            });
            showAudioType.value = false;
            uni.hideLoading();
        } catch (error: any) {
            uni.hideLoading();
            uni.showToast({
                title: error,
                icon: "none",
                duration: 3000,
            });
        }
    },
});

const openRecorder = async () => {
    showAudioType.value = false;
    await recorderRef.value?.authorize(recorderRef.value.proxy);
    showRecorder.value = true;
};

const recorderSuccess = (res: any) => {
    const { link, duration, message } = res;
    formData.audio.push({
        url: link,
        duration: duration,
        content: message,
    });
    showRecorder.value = false;
};

const { showUploadProgress, uploadMaterialList, uploadAndProcessFiles } = useUpload({
    isTranscode: true,
    videoDuration: [1, 59],
    onSuccess: (materials: any[]) => {
        if (replaceMaterialIndex.value !== -1) {
            formData.materialList[replaceMaterialIndex.value] = materials[0];
        } else {
            formData.materialList = formData.materialList.concat(materials);
        }
        replaceMaterialIndex.value = -1;
    },
});

const chooseUploadType = () => {
    showUploadTip.value = false;
    uni.showActionSheet({
        itemList: ["从相册选择图片", "从相册选择视频", "从图片素材库选择", "从视频素材库选择", "从创作库选择"],
        success: (res) => {
            const { tapIndex } = res;
            if ([0, 1].includes(tapIndex)) {
                uploadMaterialType.value = tapIndex === 0 ? "image" : "video";

                if (isFirstOpen.value) {
                    isFirstOpen.value = false;
                    showUploadTip.value = true;
                    return;
                }
                uploadAndProcessFiles(uploadMaterialType.value);
            } else if ([2, 3].includes(tapIndex)) {
                uploadMaterialType.value = tapIndex === 2 ? "image" : "video";
                showMaterialLibrary.value = true;
            } else if (tapIndex === 4) {
                showChooseHistory.value = true;
            }
        },
    });
};

const handleSelectMaterial = async (res: any[]) => {
    const type = uploadMaterialType.value;
    await processAndAppend({
        rawList: res,
        urlField: "url",
        type: type as "video" | "image",
        maxDuration: 59,
        replaceIndex: replaceMaterialIndex.value,
        onSuccess: () => (showMaterialLibrary.value = false),
    });
};

const handleSelectHistory = async (res: any[]) => {
    const normalized = res.map((item) => ({
        ...item,
        actualUrl: item.clip_result_url || item.video_result_url,
    }));

    await processAndAppend({
        rawList: normalized,
        urlField: "actualUrl",
        type: "video",
        maxDuration: 59,
        replaceIndex: replaceMaterialIndex.value,
        onSuccess: () => (showChooseHistory.value = false),
    });
};

const previewMaterial = (item: any) => {
    const { type, pic, url } = item;
    if (type === "image") {
        uni.previewImage({
            urls: [pic],
        });
    } else {
        videoPreview.poster = pic;
        videoPreview.url = url;
        showVideoPreview.value = true;
    }
};

const handleReplaceMaterial = (index: number) => {
    replaceMaterialIndex.value = index;
    chooseUploadType();
};

const handleDeleteMaterial = (index: number) => {
    formData.materialList.splice(index, 1);
};

const handleSelectTone = (tone: any) => {
    if (!tone.voice_id) {
        formData.voice = formData.anchorLists.map((item: any) => ({
            voice_id: item.voice_id,
            voice_url: item.voice_url,
            name: item.name,
        }));
        voiceValue.value = {};
    } else {
        formData.voice = [{ voice_id: tone.voice_id, voice_url: tone.voice_urls, name: tone.name }];
        voiceValue.value = tone;
    }

    showChooseTone.value = false;
};

const handleMinusVideoCount = (type: "minus" | "add") => {
    if (type === "minus") {
        if (formData.extra.video_count <= 1) {
            uni.$u.toast("视频数量最少为1");
            return;
        }
        formData.extra.video_count--;
    } else {
        if (formData.extra.video_count >= 99) {
            uni.$u.toast("视频数量最多为99");
            return;
        }
        formData.extra.video_count++;
    }
};

// 生成视频
const handleCreateVideo = async () => {
    // 判断是否有算力
    if (userTokens.value <= 0) {
        rechargePopupRef.value?.open();
        return;
    }

    if (!formData.name) {
        uni.$u.toast("请输入视频名称");
        return;
    }
    if (formData.voice.length === 0) {
        showChooseTone.value = true;
        return;
    }
    if (formData.extra.video_count <= 0) {
        uni.$u.toast("请输入视频数量");
        return;
    }
    if (formData.extra.video_count > 99) {
        uni.$u.toast("视频数量最多为99");
        return;
    }
    if (formData.extra.clip === 1 && formData.clip.length === 0) {
        uni.$u.toast("请选择视频风格");
        return;
    }
    uni.showLoading({
        title: "创建中...",
        mask: true,
    });
    try {
        const res = await createShanjianTask({
            name: formData.name,
            anchor: formData.anchorLists.map((item: any) => ({
                anchor_id: item.anchor_id,
                pic: item.pic,
                anchor_url: item.anchor_url,
                name: item.name,
            })),
            character_design: [
                {
                    name: formData.person_name,
                    introduced: formData.person_introduction,
                },
            ],
            voice: formData.voice,
            copywriting: copywriterTypeIndex.value === 0 ? formData.copywriterList : [],
            material: formData.materialList.map((item: any) => ({ fileUrl: item.url, type: item.type })),
            music: formData.music.map((item: any) => item.content),
            extra: formData.extra,
            audio: copywriterTypeIndex.value === 1 ? formData.audio.map((item: any) => item.url) : [],
            clip: formData.clip.map((item: any) => ({ clip_template_id: item })),
        });
        if (formData.person_name && formData.person_introduction) {
            addShanjianPerson({
                name: formData.person_name,
                introduced: formData.person_introduction,
            });
        }
        uni.hideLoading();
        createResult.value = res;
        showCreateSuccess.value = true;
        WechatOA.notify();
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error || "创建失败",
            icon: "none",
            duration: 3000,
        });
    }
};

// 去发布
const toPublish = () => {
    showCreateSuccess.value = false;
    uni.$u.route({
        url: "/ai_modules/digital_human/pages/montage_publish/montage_publish",
        type: "redirect",
        params: {
            task_id: JSON.stringify([createResult.value.id]),
            scene: 1,
            type: formData.shanjian_type,
        },
    });
};

const toRecord = () => {
    uni.$u.route({
        url: "/packages/pages/creation/creation",
        type: "redirect",
        params: {
            source: "1",
            type: 2,
        },
    });
};

// 获取形象列表
const getAnchorList = async (page_no: number, page_size: number) => {
    try {
        const { lists } = await getShanjianAnchorList({ page_no, page_size, status: [0, 6] });
        anchorPagingRef.value?.complete(lists);
    } catch (error) {
        anchorPagingRef.value?.complete([]);
    }
};

onLoad(() => {
    on("confirm", (res: any) => {
        const { type, data } = res;
        if (type === ListenerTypeEnum.CREATE_ANCHOR) {
            if (!data) return;
            anchorLists.value = anchorLists.value.concat(data);
        }
        if (type === ListenerTypeEnum.MONTAGE_COPYWRITER || type === ListenerTypeEnum.MONTAGE_AI_COPYWRITER) {
            if (data.length == 0) return;
            if (editCopywriterIndex.value !== -1) {
                formData.copywriterList[editCopywriterIndex.value] = data[0];
                editCopywriterIndex.value = -1;
            } else {
                formData.copywriterList = formData.copywriterList.concat(data);
            }
        }
        if (type === ListenerTypeEnum.CHOOSE_MUSIC) {
            formData.music = data.music;
            formData.extra.volume = data.volume;
        }
        if (type === ListenerTypeEnum.CHOOSE_VIDEO_STYLES) {
            if (data.length === 0) return;
            formData.clip = data;
        }
    });
});

onUnload(() => {
    destroy();
});
</script>

<style scoped lang="scss">
.copywriter-item {
    @apply relative rounded-[16rpx] bg-white shadow-[0rpx_6rpx_12rpx_0_rgba(0,0,0,0.03)] p-4;
}
.type-item {
    @apply flex flex-col items-center justify-center rounded-[16rpx] text-[#00000080] relative z-10 transition-colors duration-500 text-xs;
    &.active {
        @apply text-primary font-medium relative;
    }
}
.tab-slider {
    @apply h-[calc(100%-10rpx)] w-[50%] rounded-[16rpx] bg-white absolute top-[5rpx] left-0 transition-all duration-500;
}
</style>
