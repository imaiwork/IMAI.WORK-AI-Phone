<template>
    <view class="h-screen flex flex-col" v-if="!loading">
        <view class="grow min-h-0">
            <scroll-view
                ref="formScrollRef"
                scroll-y
                class="h-full"
                v-if="step === 1"
                :scroll-into-view="formScrollIntoView">
                <view class="p-4 pb-[350rpx]">
                    <view class="mb-6">
                        <text class="text-[34rpx] font-medium text-primary">完善运营方案</text>
                        <view class="text-[#000000]/50 text-xs mt-1">
                            基于对话已生成运营方案，你可修改补充。根据你的运营信息，AI自动填充到24h任务设置里
                        </view>
                    </view>

                    <view v-for="(item, index) in formConfigs" :key="index" class="mb-8" :id="item.key">
                        <view class="flex flex-col mb-3">
                            <text class="text-[32rpx] font-black text-[#1E293B]"
                                ><text class="text-[#FF4D4F]">*</text>{{ item.label }}</text
                            >
                            <text class="text-[24rpx] text-[#94A3B8] mt-1">{{ item.subLabel }}</text>
                        </view>

                        <view class="bg-white rounded-[24rpx] px-4 py-2 border border-[#F1F5F9]">
                            <u-input
                                type="textarea"
                                v-model="formData[item.key]"
                                placeholder-style="color: #94A3B8; font-size: 24rpx;"
                                placeholder="请输入"
                                :auto-height="true"
                                :maxlength="500"
                                :custom-style="{
                                    fontSize: '28rpx',
                                    color: '#1E293B',
                                    backgroundColor: 'transparent',
                                }" />
                        </view>
                    </view>
                </view>
            </scroll-view>
            <scroll-view scroll-y class="h-full" v-if="step === 2">
                <view class="p-4">
                    <view class="relative">
                        <view class="flex justify-end">
                            <view class="h-[150rpx] overflow-hidden">
                                <image
                                    src="@/ai_modules/device/static/images/common/analysis_avatar.png"
                                    class="w-[140rpx]"
                                    mode="widthFix"></image>
                            </view>
                        </view>
                        <view class="bg-white rounded-lg p-4 flex flex-col gap-y-2">
                            <view class="flex items-center gap-x-2">
                                <image
                                    src="@/ai_modules/device/static/images/common/check_circle.png"
                                    class="w-[40rpx] h-[40rpx]"></image>
                                <view class="text-[#000000]/90 font-medium">
                                    已分析完成 {{ completedCount }} 个信息
                                </view>
                            </view>
                            <template v-if="!isAnalyzingFinished">
                                <view class="h-[40rpx] w-[1rpx] bg-[#000000]/15 ml-2"></view>
                                <view class="flex gap-x-2">
                                    <image
                                        src="@/ai_modules/device/static/images/common/circle_loading.svg"
                                        class="w-[40rpx] h-[40rpx]"></image>
                                    <view class="text-[#000000]/90 font-medium" v-if="isAnalyzingFinished">
                                        已完成解析
                                    </view>
                                    <template v-else>
                                        <view>
                                            <view class="text-[#000000]/90 font-medium"> 思考中... </view>
                                            <view class="text-[#000000]/30">
                                                用户填入的第{{ thinkingIndex }}个{{
                                                    formConfigs[thinkingIndex - 1]?.label
                                                }}
                                            </view>
                                        </view>
                                    </template>
                                </view>
                            </template>
                        </view>
                    </view>
                    <view class="mt-4 space-y-3" v-if="isAnalyzingFinished">
                        <view class="bg-white rounded-lg px-4 pt-3 pb-6">
                            <view class="flex items-center gap-x-2">
                                <image
                                    src="@/ai_modules/device/static/images/common/tag_badge.png"
                                    class="w-[40rpx] h-[40rpx]"></image>
                                <view class="text-[34rpx] font-medium"> AI运营战略方案 </view>
                            </view>
                            <view class="text-[#000000]/70 mt-2"> 自动化才是真AI，傻瓜化才是强AI </view>
                            <view class="mt-4 text-[#000000]/70 leading-[1.7]">
                                老板，以下是我给咱公司出的AI获客解决方案。本方案基于系统<text class="text-primary"
                                    >24小时自动执行任务</text
                                >生成，不是拍脑袋的营销建议，而是真实在跑的运营系统。
                            </view>
                            <view class="mt-4 rounded-[10rpx] bg-[#f6f6f6] flex p-3 gap-x-3">
                                <view class="w-[6rpx] bg-[#DADEE5] rounded-[10rpx] shrink-0"></view>
                                <view>
                                    咱们的客户是：<text class="font-medium">{{ reportData.industryType || "--" }}</text
                                    >的<text class="font-medium"> {{ reportData.targetAudience || "--" }} </text
                                    >他们平时最爱看：<text class="text-medium"
                                        >{{ reportData.contentType1 || "--" }}、{{
                                            reportData.contentType2 || "--"
                                        }}、{{ reportData.contentType3 || "--" }}</text
                                    >等内容
                                </view>
                            </view>
                            <view class="mt-[46rpx]">
                                <view>所以系统起号的核心逻辑是：</view>
                                <view class="relative mt-[38rpx]">
                                    <image
                                        src="@/ai_modules/device/static/images/common/quote1.png"
                                        class="w-[50rpx] h-[42rpx] absolute top-[-15rpx]"></image>
                                    <view class="font-medium text-primary px-3"
                                        >不是硬推产品，而是持续输出用户爱看的内容，通过算法推荐，把人自然吸引进我们的账号池。</view
                                    >
                                    <image
                                        src="@/ai_modules/device/static/images/common/quote2.png"
                                        class="w-[50rpx] h-[42rpx] absolute bottom-[-15rpx] right-8"></image>
                                </view>
                            </view>
                        </view>
                        <view class="bg-white rounded-lg px-4 pt-3 pb-6">
                            <view class="leading-[1.7]">
                                系统会自动搭建<text class="relative"
                                    >4个IP人设矩阵<text
                                        class="absolute left-0 bottom-0 opacity-60 h-[8rpx] w-full bg-[#BAF7E7]"></text> </text
                                >，每个IP在对应适合的平台同步运行，形成4个长期稳定的流量入口。
                            </view>
                            <view class="mt-8 h-[1rpx] w-full bg-[#000000]/5"></view>
                            <view class="mt-[60rpx]">
                                <view class="flex items-center gap-x-2">
                                    <view class="w-[6rpx] h-[6rpx] bg-black rounded-full"></view>
                                    <view class="flex items-center gap-x-1 font-medium">
                                        主攻平台：<image
                                            v-if="getPlatformIcon(reportData.mainPlatform)"
                                            :src="getPlatformIcon(reportData.mainPlatform)"
                                            class="w-[40rpx] h-[40rpx]"></image>
                                        <text class="text-primary">{{ reportData.mainPlatform || "--" }}</text>
                                    </view>
                                </view>
                                <view class="p-3 rounded-[20rpx] bg-[#F5F5FE] mt-3">
                                    <text class="text-[#000000]/50">原因：</text>{{ reportData.platformLogic || "--" }}
                                </view>
                            </view>
                            <view class="mt-[60rpx]">
                                <view class="flex items-center gap-x-2">
                                    <view class="w-[6rpx] h-[6rpx] bg-black rounded-full"></view>
                                    <view class="flex items-center gap-x-1 font-medium"> 辅助平台： </view>
                                </view>
                                <view class="mt-3 space-y-2">
                                    <view class="p-3 rounded-[20rpx] bg-[#F5F5FE] mt-3 flex items-center gap-x-2">
                                        {{ reportData.auxiliaryPlatform1 || "--" }}：{{
                                            reportData.industryContent || "--"
                                        }}
                                    </view>
                                    <view class="p-3 rounded-[20rpx] bg-[#FEF4E3] mt-3 flex items-center gap-x-2">
                                        {{ reportData.auxiliaryPlatform2 || "--" }}：{{ reportData.ipContent || "--" }}
                                    </view>
                                    <view class="p-3 rounded-[20rpx] bg-[#F3FAF2] mt-3 flex items-center gap-x-2">
                                        {{ reportData.auxiliaryPlatform3 || "--" }}：{{
                                            reportData.productContent || "--"
                                        }}
                                    </view>
                                </view>
                            </view>
                        </view>
                        <view class="bg-white rounded-lg px-3 pt-3 pb-6">
                            <view class="flex gap-x-2">
                                <text class="font-medium text-[44rpx] text-[#A7CDE7]">01.</text>
                                <view class="font-medium text-[34rpx]">
                                    系统每天固定<text class="relative"
                                        >"三轮内容发布"<view
                                            class="absolute left-0 bottom-[2rpx] opacity-80 h-[8rpx] w-full bg-[#BAF7E7] rounded-[16rpx]"></view
                                    ></text>
                                </view>
                            </view>
                            <view class="mt-[46rpx] px-2 space-y-5">
                                <view class="flex gap-x-3 relative">
                                    <view
                                        class="absolute w-[2rpx] h-[60rpx] bg-[#E5E5E5] top-[50rpx] left-[8rpx]"></view>
                                    <view
                                        class="mt-[12rpx] shrink-0 w-[18rpx] h-[18rpx] border-2 border-solid border-[#A7CDE7] rounded-full bg-white">
                                    </view>
                                    <view>
                                        <view class="text-[34rpx] text-primary text-medium"> 08:00 - 08:30 </view>
                                        <view class="mt-1">
                                            {{ reportData.industryContent || "--" }}
                                        </view>
                                    </view>
                                </view>
                                <view class="flex gap-x-3 relative">
                                    <view
                                        class="absolute w-[2rpx] h-[60rpx] bg-[#E5E5E5] top-[50rpx] left-[8rpx]"></view>
                                    <view
                                        class="mt-[12rpx] shrink-0 w-[18rpx] h-[18rpx] border-2 border-solid border-[#A7CDE7] rounded-full bg-white">
                                    </view>
                                    <view>
                                        <view class="text-[34rpx] text-primary text-medium"> 09:00 - 09:30 </view>
                                        <view class="mt-1">
                                            {{ reportData.ipContent || "--" }}
                                        </view>
                                    </view>
                                </view>
                                <view class="flex gap-x-3">
                                    <view
                                        class="mt-[12rpx] shrink-0 w-[18rpx] h-[18rpx] border-2 border-solid border-[#A7CDE7] rounded-full bg-white">
                                    </view>
                                    <view>
                                        <view class="text-[34rpx] text-primary text-medium"> 10:00 - 10:30 </view>
                                        <view class="mt-1">
                                            {{ reportData.productContent || "--" }}
                                        </view>
                                    </view>
                                </view>
                            </view>
                            <view class="px-[42rpx] py-[32rpx] bg-[#F5F5FE] mt-[50rpx] rounded-[20rpx]">
                                每天<text class="text-primary">4个</text>平台总计<text class="text-primary">12条</text
                                >内容自动产出， 一个月稳定输出约<text class="text-primary">360条</text>内容。
                            </view>
                            <view class="mt-8 h-[1rpx] w-full bg-[#000000]/5"></view>
                            <view class="flex gap-x-2 mt-[60rpx]">
                                <text class="font-medium text-[44rpx] text-[#A7CDE7]">02.</text>
                                <view class="font-medium text-[34rpx]">
                                    <view class=""> 系统每天固定以下时间段 </view>
                                    <view class="relative w-fit"
                                        >"回复私信信息"<view
                                            class="absolute left-0 bottom-[4rpx] opacity-60 h-[8rpx] w-full bg-[#BAF7E7] rounded-[16rpx]"></view
                                    ></view>
                                </view>
                            </view>
                            <view class="bg-[#FEF4E3] rounded-[20rpx] p-[32rpx] mt-[60rpx]">
                                06:00-06:30、06:30-07:00、07:00-7:30
                            </view>
                            <view class="mt-8 h-[1rpx] w-full bg-[#000000]/5"></view>
                            <view class="flex gap-x-2 mt-[60rpx]">
                                <text class="font-medium text-[44rpx] text-[#A7CDE7]">03.</text>
                                <view class="font-medium text-[34rpx]">
                                    <view class=""> 系统每天凌晨会在视频号 </view>
                                    <view class="relative w-fit"
                                        >"线索词获客"<view
                                            class="absolute left-0 bottom-[4rpx] opacity-60 h-[8rpx] w-full bg-[#BAF7E7] rounded-[16rpx]"></view
                                    ></view>
                                </view>
                            </view>
                            <view class="bg-[#F3FAF2] rounded-[20rpx] p-[32rpx] mt-[60rpx]">
                                搜索：{{ reportData.mainLeadTopic || "--" }}
                            </view>
                            <view class="mt-4 text-[#000000]/50">
                                主动触达意向客户主页并进行私信互动，避开白天高竞争时段，提高触达成功率。
                            </view>
                            <view class="mt-8 h-[1rpx] w-full bg-[#000000]/5"></view>
                            <view class="flex gap-x-2 mt-[60rpx]">
                                <text class="font-medium text-[44rpx] text-[#A7CDE7]">04.</text>
                                <view class="font-medium text-[34rpx]">
                                    <view class=""> 系统每天在多个平台执行 </view>
                                    <view class="relative w-fit"
                                        >"评论区截流"<view
                                            class="absolute left-0 bottom-[4rpx] opacity-60 h-[8rpx] w-full bg-[#BAF7E7] rounded-[16rpx]"></view
                                    ></view>
                                </view>
                            </view>
                            <view class="mt-[48rpx] space-y-2">
                                <view class="px-3 py-3 rounded-[20rpx] bg-[#F5F5FE] mt-3 flex items-center gap-x-2">
                                    <image src="/static/images/common/douyin_s.png" class="w-[40rpx] h-[40rpx]"></image
                                    >抖音：约 30条
                                </view>
                                <view class="px-3 py-3 rounded-[20rpx] bg-[#F5F5FE] mt-3 flex items-center gap-x-2">
                                    <image src="/static/images/common/redbook_s.png" class="w-[40rpx] h-[40rpx]"></image
                                    >小红书：约 10条
                                </view>
                                <view class="px-3 py-3 rounded-[20rpx] bg-[#FEF4E3] mt-3 flex items-center gap-x-2">
                                    <image src="/static/images/common/sph_s.png" class="w-[40rpx] h-[40rpx]"></image
                                    >视频号：点赞约 50个
                                </view>
                                <view class="px-3 py-3 rounded-[20rpx] bg-[#F3FAF2] mt-3 flex items-center gap-x-2">
                                    <image
                                        src="/static/images/common/kuaishou_s.png"
                                        class="w-[40rpx] h-[40rpx]"></image
                                    >快手：约 50条
                                </view>
                            </view>
                            <view class="mt-[44rpx]">
                                合计每天至少<text class="text-primary">120次</text>精准互动触达。
                            </view>
                        </view>
                        <view class="bg-white rounded-lg px-3 pt-3 pb-6">
                            <view>
                                <image
                                    src="@/ai_modules/device/static/images/common/quote1.png"
                                    class="w-[50rpx] h-[42rpx]"></image>
                                <view class="text-[34rpx] font-medium my-1">
                                    <text class="relative">
                                        前7天以养号为主<view
                                            class="absolute left-0 bottom-[2rpx] opacity-80 h-[8rpx] w-full bg-[#BAF7E7] rounded-[16rpx]"></view> </text
                                    >，不做激进营销行为，模拟真人使用轨迹，让平台判定为优质正常账号。
                                </view>
                                <view class="text-right">
                                    <image
                                        src="@/ai_modules/device/static/images/common/quote2.png"
                                        class="w-[50rpx] h-[42rpx]"></image>
                                </view>
                            </view>
                            <view class="mt-[44rpx] bg-[#F5F5FE] rounded-[20rpx] p-3">
                                每天晚上系统会自动刷视频、点赞、收藏，保持账号活跃度，降低限流与封号风险。
                            </view>
                            <view class="mt-[60rpx]">
                                <view> 基于当前任务强度，保守预估： </view>
                                <view class="mt-3 space-y-2">
                                    <view class="bg-[#F5F5FE] rounded-[20rpx] p-3">
                                        • 每天主动触达：<text class="font-medium">{{
                                            reportData.dailyReach || "--"
                                        }}</text>
                                    </view>
                                    <view class="bg-[#FEF4E3] rounded-[20rpx] p-3">
                                        • 每天新增私域：<text class="font-medium">{{
                                            reportData.dailyPrivateLeads || "--"
                                        }}</text>
                                    </view>
                                    <view class="bg-[#F3FAF2] rounded-[20rpx] p-3">
                                        • 每天有效意向：<text class="font-medium">{{
                                            reportData.dailyValidIntents || "--"
                                        }}</text>
                                    </view>
                                </view>
                            </view>
                            <view class="mt-[90rpx]">
                                <view class="flex items-center gap-x-2">
                                    <image
                                        src="@/ai_modules/device/static/images/common/success_primary.png"
                                        class="w-[32rpx] h-[32rpx]"></image>
                                    <text class="text-[34rpx] font-medium text-primary">自动化才是真AI</text>
                                </view>
                                <view class="flex items-center gap-x-2 mt-2">
                                    <image
                                        src="@/ai_modules/device/static/images/common/success_primary.png"
                                        class="w-[32rpx] h-[32rpx]"></image>
                                    <text class="text-[34rpx] font-medium text-primary">傻瓜化才是强AI。</text>
                                </view>
                            </view>
                            <view class="text-[#000000]/50 mt-[26rpx]">
                                这是持续稳定型获客系统，不是靠运气的爆款玩法。
                            </view>
                            <view class="bg-[#F5F5FE] rounded-[20rpx] p-3 mt-[48rpx]">
                                <view> 您只管做好产品和服务， </view>
                                <view> 获客的事，交给我。 </view>
                            </view>
                        </view>
                        <view class="mt-[48rpx] px-6"> 接下来，您只需要上传素材，其他的交给我就好。 </view>
                    </view>
                </view>
            </scroll-view>
            <scroll-view scroll-y class="h-full" v-if="step === 3">
                <view class="p-4 pb-[150rpx]">
                    <view class="rounded-[20rpx] bg-white px-[36rpx] py-[22rpx] relative">
                        <view class="flex items-center justify-between">
                            <view class="font-medium text-[30rpx]">社媒账号</view>
                            <view class="text-[#000000]/50" @click="showGetAccountPopup = true"
                                >详情
                                <u-icon name="arrow-right" color="#9DA5B0" size="20"></u-icon>
                            </view>
                        </view>
                        <view class="flex items-center gap-x-2 mt-[22rpx]">
                            <view v-for="(item, index) in sortedPlatform" :key="index">
                                <image :src="item.icon" class="w-[48rpx] h-[48rpx]"></image>
                            </view>
                        </view>
                    </view>
                    <view
                        class="mt-2 rounded-[20rpx] bg-white px-[36rpx] py-4 flex items-center justify-between"
                        @click="step = 2">
                        <view class="font-medium text-[30rpx]"> 运营策略方案 </view>
                        <view class="text-[#000000]/50"
                            >查看
                            <u-icon name="arrow-right" color="#9DA5B0" size="20"></u-icon>
                        </view>
                    </view>
                    <view class="mt-4 flex flex-col gap-y-[50rpx] pb-[100rpx]">
                        <view>
                            <view class="flex items-center justify-between">
                                <view class="text-[30rpx] font-medium">
                                    <text class="text-[#FF2442]">*</text>
                                    数字人形象({{ anchorList.length }})
                                </view>
                                <view class="text-xs font-medium text-primary" @click="toPage('anchor_material')">
                                    添加<u-icon name="arrow-right" color="#0065FB" size="20"></u-icon>
                                </view>
                            </view>
                            <view class="rounded-[20rpx] bg-white p-[30rpx] mt-[18rpx]">
                                <view v-if="anchorList.length > 0" class="grid grid-cols-3 gap-x-[20rpx]">
                                    <view
                                        v-for="(item, index) in anchorList.slice(0, 3)"
                                        :key="index"
                                        class="aspect-[3/4] rounded-[20rpx] overflow-hidden relative">
                                        <image :src="item.pic" class="w-full h-full" mode="aspectFill"></image>
                                        <view
                                            class="absolute top-0 left-0 w-full h-full flex items-center justify-center z-[222]">
                                            <image
                                                src="/static/images/icons/play.svg"
                                                class="w-[48rpx] h-[48rpx]"
                                                @click="previewVideo(item)"></image>
                                        </view>
                                    </view>
                                </view>
                                <view v-else class="flex flex-col items-center justify-center gap-y-[20rpx] py-4">
                                    <view class="text-center text-[#0000004d]">你还没有添加数字人形象</view>
                                    <view class="text-primary font-medium" @click="toPage('anchor_material')">
                                        去添加
                                    </view>
                                </view>
                            </view>
                        </view>
                        <view>
                            <view class="flex items-center justify-between">
                                <view class="text-[30rpx] font-medium">
                                    <text class="text-[#FF2442]">*</text>
                                    视频剪辑素材({{ videoList.length }})
                                </view>
                                <view class="text-xs font-medium text-primary" @click="toPage('video_material')">
                                    添加<u-icon name="arrow-right" color="#0065FB" size="20"></u-icon>
                                </view>
                            </view>
                            <view class="rounded-[20rpx] bg-white p-[30rpx] mt-[18rpx]">
                                <view class="grid grid-cols-3 gap-x-[20rpx]" v-if="videoList.length > 0">
                                    <view
                                        v-for="(item, index) in videoList.slice(0, 3)"
                                        :key="index"
                                        class="aspect-[3/4] rounded-[20rpx] relative overflow-hidden">
                                        <image :src="item.pic" class="w-full h-full" mode="aspectFill"></image>
                                        <view
                                            class="absolute bottom-0 h-[40rpx] w-full bg-[#00000080] flex items-center justify-center z-[88]">
                                            <image
                                                v-if="item.type === 'image'"
                                                src="@/ai_modules/digital_human/static/icons/pic.svg"
                                                class="w-[24rpx] h-[24rpx]"></image>
                                            <image
                                                v-else
                                                src="@/ai_modules/digital_human/static/icons/video.svg"
                                                class="w-[24rpx] h-[24rpx]"></image>
                                        </view>
                                        <view
                                            class="absolute top-0 left-0 w-full h-full flex items-center justify-center z-[222]">
                                            <image
                                                src="/static/images/icons/play.svg"
                                                class="w-[48rpx] h-[48rpx]"
                                                @click="previewVideo(item)"></image>
                                        </view>
                                    </view>
                                </view>
                                <view v-else class="flex flex-col items-center justify-center gap-y-[20rpx] py-4">
                                    <view class="text-center text-[#0000004d]">你还没有添加视频剪辑素材</view>
                                    <view class="text-primary font-medium" @click="toPage('video_material')">
                                        去添加
                                    </view>
                                </view>
                            </view>
                        </view>
                        <view>
                            <view class="flex items-center justify-between">
                                <view class="text-[30rpx] font-medium">
                                    <text class="text-[#FF2442]">*</text>
                                    图文剪辑素材({{ imageList.length }})
                                </view>
                                <view class="text-xs font-medium text-primary" @click="toPage('image_material')">
                                    添加<u-icon name="arrow-right" color="#0065FB" size="20"></u-icon>
                                </view>
                            </view>
                            <view class="rounded-[20rpx] bg-white p-[30rpx] mt-[18rpx]">
                                <view class="grid grid-cols-3 gap-x-[20rpx]" v-if="imageList.length > 0">
                                    <view
                                        class="aspect-[3/4] rounded-[20rpx]"
                                        v-for="(item, index) in imageList.slice(0, 3)"
                                        :key="index">
                                        <image
                                            :src="item.url"
                                            class="w-full h-full rounded-[20rpx]"
                                            mode="aspectFill"></image>
                                    </view>
                                </view>
                                <view v-else class="flex flex-col items-center justify-center gap-y-[20rpx] py-4">
                                    <view class="text-center text-[#0000004d]">你还没有添加图文剪辑素材</view>
                                    <view class="text-primary font-medium" @click="toPage('image_material')">
                                        去添加
                                    </view>
                                </view>
                            </view>
                        </view>
                    </view>
                </view>
            </scroll-view>
        </view>
        <view class="shrink-0">
            <view class="p-4">
                <view class="flex items-center gap-4" v-if="step === 1">
                    <view
                        class="flex-1 h-[96rpx] rounded-full bg-white flex items-center justify-center"
                        @click="openRecorder">
                        <u-icon name="mic" color="#0065fb" size="32"></u-icon>
                        <text class="text-primary text-[32rpx] font-black tracking-wide ml-2">点击说话</text>
                    </view>
                    <view
                        class="flex-1 h-[96rpx] rounded-full bg-primary flex items-center justify-center"
                        @click="startAnalysis">
                        <text class="text-white text-[32rpx] font-black tracking-wide ml-2">开始分析</text>
                    </view>
                </view>

                <view class="flex items-center justify-between gap-4" v-if="step === 2">
                    <view class="flex items-center gap-x-1" @click="handleResetDialog">
                        <u-icon name="reload" size="28"></u-icon>
                        <text>重新对话</text>
                    </view>
                    <view class="flex-1">
                        <u-button
                            type="primary"
                            shape="circle"
                            :ripple="true"
                            :custom-style="{
                                height: '96rpx',
                                fontSize: '30rpx',
                                fontWeight: '900',
                                backgroundColor: '#0065fb',
                                border: 'none',
                                boxShadow: '0 10rpx 30rpx rgba(0, 101, 251, 0.3)',
                            }"
                            @click="handleConfirmStep2">
                            上传素材
                        </u-button>
                    </view>
                </view>
                <view v-if="step === 3">
                    <u-button
                        type="primary"
                        shape="circle"
                        :ripple="true"
                        :custom-style="{
                            height: '96rpx',
                            fontSize: '30rpx',
                            fontWeight: '900',
                            boxShadow: '0 10rpx 30rpx rgba(0, 101, 251, 0.3)',
                        }"
                        @click="handleConfirmStep3">
                        确定保存
                    </u-button>
                </view>
            </view>
        </view>
    </view>
    <recorder-control
        v-model="showRecorder"
        ref="recorderRef"
        @close="showRecorder = false"
        @success="recorderSuccess" />
    <video-preview v-model="showVideoPreview" :video-url="playData.url" :poster="playData.pic"></video-preview>
    <account-get
        v-if="showGetAccountPopup"
        v-model="showGetAccountPopup"
        :sorted-platform="sortedPlatform"
        @get-account="handleGetAccount(deviceCode, false)" />

    <confirm-dialog
        v-if="showCreateSuccessDialog"
        v-model="showCreateSuccessDialog"
        center
        content="创建成功，返回上一页面？"
        @cancel="handleCancelCreateSuccess"
        @confirm="handleConfirmCreateSuccess"></confirm-dialog>
    <recharge-popup ref="rechargePopupRef"></recharge-popup>
</template>

<script setup lang="ts">
import {
    getDeviceDetail,
    getAutoTaskDetail as getAutoTaskDetailApi,
    createAutoTask as createAutoTaskApi,
    createAutoTaskPublishConfig,
    marketingAnalysis,
    addMarketingAnalysisData,
    updateMarketingAnalysisData,
    marketingAnalysisReport,
    marketingAnalysisDetail,
} from "@/api/device";
import requestCancel from "@/utils/request/cancel";
import { useUserStore } from "@/stores/user";
import { getRect, setFormData } from "@/utils/util";
import { useDevice } from "@/ai_modules/device/hooks/useDevice";
import useMaterialStore from "@/ai_modules/device/stores/material";
import RecorderControl from "@/ai_modules/device/components/recorder-control/recorder-control.vue";
import AccountGet from "@/ai_modules/device/components/account-get/account-get.vue";
import XhsIcon from "@/static/images/common/redbook_s.png";
import DouyinIcon from "@/static/images/common/douyin_s.png";
import KuaishouIcon from "@/static/images/common/kuaishou_s.png";
import SphIcon from "@/static/images/common/sph_s.png";

interface AnalysisStep {
    key: string;
    label: string;
    subLabel: string;
    placeholder?: string;
    status: 0 | 1 | 2 | 3; //  0: 未开始, 1: 已完成, 2: 进行中 3: 失败
}

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

const formData = reactive<any>({
    contents: "",
    basicInformation: "", //基础信息
    ipTalent: "", //IP人选
    ipStyle: "", //IP风格
    targetCustomers: "", //目标客户
    productServiceFeatures: "", //产品/服务特点
    brandStory: "", //品牌故事
    contentPreferences: "", //内容偏好
    brandAchievementsPositioning: "", //品牌成就与位置
    accountStage: "", //账号阶段
});

const formConfigs = ref<AnalysisStep[]>([
    {
        key: "basicInformation",
        label: "基础信息",
        subLabel: "品牌/门店名称；所属行业；业务类型(门店/线上/品牌/个人)等",
        status: 0,
    },
    {
        key: "ipTalent",
        label: "IP人选",
        subLabel: "谁来当IP（老板/员工/店长/虚拟人物等）",
        status: 0,
    },
    {
        key: "ipStyle",
        label: "IP人设风格",
        subLabel: "从专业权威、亲切邻家、幽默搞笑、热血励志、精致美学、真实接地气中选1-3个关键词，或补充其他风格",
        status: 0,
    },
    {
        key: "targetCustomers",
        label: "目标客户",
        subLabel: "年龄范围、性别比例、群体描述（例如“25-40岁女性，中小企业白领”）",
        status: 0,
    },
    {
        key: "productServiceFeatures",
        label: "产品/服务特点",
        subLabel: "用简单短语或一句话总结最大的3个特点",
        status: 0,
    },
    {
        key: "brandStory",
        label: "品牌故事",
        subLabel: "创业/经营中最打动人的一个小故事（1~2句话即可）",
        status: 0,
    },
    {
        key: "contentPreferences",
        label: "内容偏好",
        subLabel: "倾向于口播、剧情、知识分享、幕后记录、客户见证等哪种形式？是否有不愿尝试的形式？",
        status: 0,
    },
    {
        key: "brandAchievementsPositioning",
        label: "品牌成就与位置",
        subLabel: "获得过的奖项、亮点事件，以及城市/地理位置（例如“深圳南山区”）",
        status: 0,
    },
    {
        key: "accountStage",
        label: "目前账号阶段",
        subLabel: "用于优先解决最关键的问题并调整生成策略",
        placeholder: "请输入，如：涨粉困难、内容创作瓶颈、变现路径不清晰",
        status: 0,
    },
]);

const step = ref<number>(-1);
const loading = ref(false);
const deviceCode = ref<string>("");
const detail = ref<any>({});
const showRecorder = ref(false);
const scrollTop = ref<number>(0);
const recorderRef = ref<InstanceType<typeof RecorderControl> | null>(null);
const visibleSteps = ref<AnalysisStep[]>([]);
const stepsScrollTop = ref<number>(0);
const formDataId = ref<number>();
const isAnalyzingFinished = ref(false);
const reportData = ref<any>({});
const formScrollRef = ref<any>(null);
const formScrollIntoView = ref<string>("");
const { sortedPlatform, initializePlatform, handleGetAccount, connectWebSocket } = useDevice();
const showGetAccountPopup = ref(false);
const showCreateSuccessDialog = ref(false);
const rechargePopupRef = shallowRef();
const materialStore = useMaterialStore();
const { anchorList, videoList, imageList } = storeToRefs(materialStore);
const showVideoPreview = ref(false);
const playData = ref<{ url: string; pic: string }>({ url: "", pic: "" });
const completedCount = computed(() => {
    return visibleSteps.value.filter((item) => item.status === 1).length;
});
const thinkingIndex = computed(() => {
    return visibleSteps.value.findIndex((item) => item.status === 2) + 1;
});

// 定义一个定时器变量
const analysisTimer = ref<any>(null);

const openRecorder = async () => {
    await recorderRef.value?.authorize(recorderRef.value.proxy);
    showRecorder.value = true;
};

const recorderSuccess = async (res: any) => {
    showRecorder.value = false;
    const { message } = res;
    uni.showLoading({
        title: "分析中...",
        mask: true,
    });
    try {
        const { result } = await marketingAnalysis({
            contents: message,
        });
        if (result?.Analysis_Form) {
            setFormData(result?.Analysis_Form, formData);
            formData.contents = message || "";
            await saveFormData(2);
            uni.hideLoading();
            uni.showToast({
                title: "分析成功",
                icon: "none",
                duration: 3000,
            });
        } else {
            uni.hideLoading();
            uni.showToast({
                title: "分析失败，请重试",
                icon: "none",
                duration: 3000,
            });
        }
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error || "分析失败，请稍后重试",
            icon: "none",
            duration: 3000,
        });
    }
};

// 保存form数据
const saveFormData = async (type: 1 | 2) => {
    const res = formDataId.value
        ? await updateMarketingAnalysisData({
              id: formDataId.value,
              device_code: deviceCode.value,
              step: type,
              ...formData,
          })
        : await addMarketingAnalysisData({
              device_code: deviceCode.value,
              ...formData,
          });
    formDataId.value = res.id;
};

// 统一提示“正在对话中”提取函数
const chattingBeginToast = () => {
    uni.$u.toast("当前还有对话中，请稍等");
};

const startAnalysis = async () => {
    // 清除之前的定时器
    if (analysisTimer.value) {
        clearTimeout(analysisTimer.value);
        analysisTimer.value = null;
    }

    // 验证表单
    const formDataItem = formConfigs.value.find((item: any) => formData[item.key] === "");
    if (formDataItem) {
        formScrollIntoView.value = formDataItem.key;
        uni.$u.toast(`请填写${formDataItem.label}`);
        return;
    }

    step.value = 2;
    const showStep = async (index: number, duration = 800) => {
        if (index >= formConfigs.value.length) return;

        formConfigs.value[index].status = 2;
        visibleSteps.value.push({ ...formConfigs.value[index] });
        scrollToStepsBottom();

        await new Promise((resolve) => (analysisTimer.value = setTimeout(resolve, duration)));

        formConfigs.value[index].status = 1;
        visibleSteps.value[index] = { ...formConfigs.value[index] };
    };

    try {
        // 先保存
        await saveFormData(1);
        // 显示前3个快速步骤
        for (let i = 0; i < 7; i++) {
            await showStep(i, 2000);
        }

        // 显示第4、5步骤（稍慢）
        await showStep(7, 2500);
        await showStep(8, 1000);

        const lastIndex = formConfigs.value.length - 1;

        // 开始实际分析，显示最后一步
        formConfigs.value[lastIndex].status = 2;
        visibleSteps.value.push({ ...formConfigs.value[lastIndex] });
        scrollToStepsBottom();

        if (userTokens.value <= 0) {
            rechargePopupRef.value?.open();
            formConfigs.value[lastIndex].status = 3;
            return;
        }
        const res = await marketingAnalysisReport({
            device_code: deviceCode.value,
            contents: {
                Operations: "",
                Analysis_Form: formData,
            },
        });
        reportData.value = res.result?.Operations;

        // 完成最后一步
        formConfigs.value[lastIndex].status = 1;
        visibleSteps.value[lastIndex] = { ...formConfigs.value[lastIndex] };

        isAnalyzingFinished.value = true;
    } catch (error) {
        console.log(error);
    }
};

const handleResetDialog = () => {
    uni.showModal({
        title: "提示",
        content: "确定要重新对话吗？",
        success: (res) => {
            if (res.confirm) {
                // 清除定时器
                if (analysisTimer.value) {
                    clearTimeout(analysisTimer.value);
                    analysisTimer.value = null;
                }

                step.value = 1;
                isAnalyzingFinished.value = false;
                reportData.value = {};
                visibleSteps.value = [];
                formConfigs.value.forEach((item) => {
                    item.status = 0;
                });
                requestCancel.remove("/auto.needsAnalysis/report");
            }
        },
    });
};

const { proxy }: any = getCurrentInstance();
const scrollToStepsBottom = async () => {
    await nextTick();
    getRect(".steps-container", false, proxy).then((res: any) => {
        stepsScrollTop.value = res.height;
    });
};

const handleConfirmStep2 = async () => {
    if (!isAnalyzingFinished.value) {
        uni.$u.toast("正在解析中，请稍后重试");
        return;
    }
    uni.showLoading({
        title: "数据保存中...",
        mask: true,
    });
    try {
        await createAutoTask();
        scrollTop.value = 0;
        step.value = 3;
        uni.hideLoading();
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error || "数据保存失败，请稍后重试",
            icon: "none",
            duration: 3000,
        });
    }
};

const getPlatformIcon = (type: string) => {
    if (!type) return "";
    if (type.includes("抖音")) {
        return DouyinIcon;
    } else if (type.includes("快手")) {
        return KuaishouIcon;
    } else if (type.includes("小红书")) {
        return XhsIcon;
    } else if (type.includes("视频号")) {
        return SphIcon;
    }
    return "";
};

const previewVideo = (item: any) => {
    playData.value = { url: item.url, pic: item.pic };
    showVideoPreview.value = true;
};

const toPage = (page: string) => {
    const urls = {
        anchor_material: "/ai_modules/device/pages/anchor_material/anchor_material",
        video_material: "/ai_modules/device/pages/video_material/video_material",
        image_material: "/ai_modules/device/pages/image_material/image_material",
    };
    uni.$u.route({ url: urls[page as keyof typeof urls] });
};

const handleConfirmStep3 = async () => {
    // 这里要判断平台有一个激活了,如果不是则弹窗拉取
    // const isSomeActive = sortedPlatform.value.some((item) => item.status == 2);
    // if (!isSomeActive) {
    //     uni.$u.toast("请先激活相关平台，再进行下一步操作");
    //     showGetAccountPopup.value = true;
    //     return;
    // }
    if (materialStore.anchorList.length == 0) {
        uni.$u.toast("请选择形象");
        return;
    }
    if (!materialStore.videoList.length) {
        uni.$u.toast("请选择视频素材");
        return;
    }
    if (materialStore.imageList.length == 0) {
        uni.$u.toast("请选择图文素材");
        return;
    }
    uni.showLoading({
        title: "数据保存中...",
        mask: true,
    });
    try {
        const result = await createAutoTask();
        await createAutoTaskPublishConfig({
            text_theme: "",
            video_theme: "",
            device_code: deviceCode.value,
            device_config_id: result.id,
            ...getCreateAutoTaskParams(),
        });
        showCreateSuccessDialog.value = true;
        uni.hideLoading();
    } catch (error: any) {
        uni.hideLoading();
        uni.showToast({
            title: error || "数据保存失败，请稍后重试",
            icon: "none",
            duration: 3000,
        });
    }
};

// 提取createAutoTask的参数
const getCreateAutoTaskParams = () => {
    return {
        human_image: materialStore.anchorList.map((item: any) => ({
            ...item.anchor_ids,
            ...item.extra_info,
            id: item.id,
            pic: item.pic,
            width: item.width,
            height: item.height,
            voice_id: item.extra_info.shanjian_voice_id,
            anchor_url: item.url,
        })),
        clip_material: materialStore.videoList.map((item: any) => ({
            type: item.type,
            fileUrl: item.url,
            cover: item.pic,
            duration: item.duration || 0,
        })),
        image_material: materialStore.imageList.map((item: any) => item.url),
    };
};

const createAutoTask = (): Promise<any> => {
    return new Promise((resolve, reject) => {
        const params = {
            contentType1: reportData.contentType1,
            contentType2: reportData.contentType2,
            contentType3: reportData.contentType2,
            device_code: deviceCode.value,
            ...formData,
            ...getCreateAutoTaskParams(),
        };
        createAutoTaskApi(params)
            .then((res: any) => {
                resolve(res);
            })
            .catch((error: any) => {
                reject(error);
            });
    });
};

const handleConfirmCreateSuccess = () => {
    uni.$u.route({
        url: "/ai_modules/device/pages/auto_task/auto_task",
        type: "reLaunch",
        params: { device_code: deviceCode.value },
    });
};

const handleCancelCreateSuccess = () => {
    uni.$u.route({
        url: "/ai_modules/device/pages/auto_task/auto_task",
        type: "reLaunch",
    });
};

const getDetail = async () => {
    const data = await getDeviceDetail({ device_code: deviceCode.value });
    const { accounts } = data;
    detail.value = data;
    initializePlatform(accounts);
    connectWebSocket();
};

const getAutoTaskDetail = async () => {
    const res = await getAutoTaskDetailApi({ device_code: deviceCode.value });
    const analysisRes = await marketingAnalysisDetail({
        device_code: deviceCode.value,
    });
    reportData.value = analysisRes?.report?.result?.Operations || {};
    materialStore.anchorList = res.human_image.map((item: any) => ({
        ...item,
        extra_info: {
            shanjian_voice_id: item.shanjian_voice_id,
        },
        url: item.anchor_url,
        anchor_ids: {
            shanjian_anchor_id: item.shanjian_anchor_id,
            weiju_anchor_id: item.weiju_anchor_id,
            chanjing_anchor_id: item.chanjing_anchor_id,
        },
    }));
    materialStore.videoList = res.clip_material.map((item: any) => ({
        ...item,
        type: item.type,
        url: item.fileUrl,
        pic: item.cover,
    }));
    materialStore.imageList = res.image_material.map((item: any) => ({
        url: item,
    }));
    setFormData(analysisRes?.analysis?.result?.Analysis_Form || {}, formData);
    // 判断是不是需要直接显示到第二步
    const { contentType1, contentType2, contentType3 } = reportData.value;
    if (contentType1 && contentType2 && contentType3) {
        step.value = 2;
        formConfigs.value.forEach((item: any) => {
            item.status = 1;
            visibleSteps.value.push({ ...item });
        });
        isAnalyzingFinished.value = true;
        // 设置
    } else {
        step.value = 1;
    }
};

const init = async () => {
    uni.showLoading({
        title: "加载中...",
    });
    try {
        await Promise.allSettled([getDetail(), getAutoTaskDetail()]);
    } finally {
        uni.hideLoading();
        loading.value = false;
    }
};

onLoad((options: any) => {
    deviceCode.value = options.device_code;
    init();
});
</script>
