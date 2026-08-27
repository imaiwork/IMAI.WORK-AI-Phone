<template>
    <!-- mask 不可关：面板内是已付费的搜索/分析结果，误触蒙层不应直接丢弃 -->
    <popup-bottom
        v-model="show"
        height="85%"
        custom-class="bg-[#F3F5FA]"
        :clearable="false"
        :mask-close-able="false"
        @input="emit('update:modelValue', $event)">
        <template #header>
            <view class="bg-[#F3F5FA] px-[32rpx] pt-[20rpx] pb-[16rpx]">
                <view class="w-[72rpx] h-[8rpx] mx-auto mb-[20rpx] bg-[#E5E7EB] rounded-full" />
                <view class="flex items-start justify-between gap-[24rpx]">
                    <view class="flex-1 min-w-0">
                        <text class="block text-[34rpx] font-bold text-[#111827] leading-snug line-clamp-2">
                            {{ topic?.title || "—" }}
                        </text>
                        <text class="block text-xs text-[#9CA3AF] mt-[8rpx]">{{ subTitle }}</text>
                    </view>
                    <view
                        class="w-[56rpx] h-[56rpx] rounded-full bg-[#E9EBF0] flex items-center justify-center flex-shrink-0"
                        @click="emit('update:modelValue', false)">
                        <u-icon name="close" color="#8B9199" :size="18"></u-icon>
                    </view>
                </view>
            </view>
        </template>
        <template #content>
            <view class="h-full w-full flex flex-col">
                <view class="grow min-h-0">
                    <scroll-view scroll-y class="h-full">
                        <view class="px-[32rpx] pb-[600rpx]">
                            <!-- 已还原上次的分析记录（含停留步骤的设置与文案） -->
                            <view
                                v-if="fromCache"
                                class="flex items-center justify-between gap-[16rpx] px-[20rpx] py-[14rpx] rounded-[16rpx] bg-[#EFF6FF] mt-[16rpx]">
                                <text class="flex-1 min-w-0 text-[22rpx] text-primary line-clamp-1">
                                    已载入上次的分析记录{{ cachedAt ? " · " + formatCachedAt(cachedAt) : "" }}
                                </text>
                                <text class="flex-shrink-0 text-[22rpx] font-bold text-primary" @click="reResearch">
                                    重新分析
                                </text>
                            </view>

                            <!-- 步骤 1 AI 联网搜索 -->
                            <view class="flex items-center gap-[12rpx] mt-[24rpx] mb-[16rpx]">
                                <view class="step-no bg-primary"><text class="text-white">1</text></view>
                                <text class="text-[29rpx] font-bold text-[#111827]">AI 联网搜索</text>
                            </view>
                            <view v-if="researchErr" class="err-box">
                                <view class="flex-shrink-0 mt-[4rpx]">
                                    <u-icon name="error-circle" color="#EF4444" :size="26"></u-icon>
                                </view>
                                <text class="flex-1 text-xs text-[#EF4444] leading-relaxed">{{ researchErr }}</text>
                            </view>
                            <view v-else-if="!research" class="flow-card flex items-center gap-[20rpx]">
                                <u-loading mode="circle" size="40" color="#0065fb"></u-loading>
                                <view>
                                    <text class="block text-sm font-semibold text-primary">正在联网搜索这个热点…</text>
                                    <text class="block text-[22rpx] text-[#9CA3AF] mt-[4rpx]">
                                        AI 正在抓取最新报道与讨论
                                    </text>
                                </view>
                            </view>
                            <template v-else>
                                <view class="flow-card">
                                    <template v-if="research.core_points?.length">
                                        <view
                                            v-for="(point, pi) in research.core_points"
                                            :key="pi"
                                            class="flex gap-[18rpx] py-[14rpx]"
                                            :class="
                                                pi ? 'border-[0] border-t-[2rpx] border-solid border-[#F9FAFB]' : ''
                                            ">
                                            <view
                                                class="w-[32rpx] h-[32rpx] rounded-[8rpx] bg-[#EFF6FF] flex items-center justify-center flex-shrink-0 mt-[2rpx]">
                                                <text class="text-[20rpx] font-bold text-primary">{{ pi + 1 }}</text>
                                            </view>
                                            <view class="flex-1 min-w-0">
                                                <text class="block text-[26rpx] font-semibold text-[#111827]">
                                                    {{ point.label }}
                                                </text>
                                                <text
                                                    class="block text-[24rpx] text-[#6B7280] leading-relaxed mt-[4rpx] break-all">
                                                    {{ point.detail }}
                                                </text>
                                            </view>
                                        </view>
                                    </template>
                                    <text v-else class="text-[25rpx] text-[#6B7280] leading-relaxed break-all">
                                        {{ (research.summary || "").slice(0, 300) }}
                                    </text>
                                </view>
                                <view v-if="research.citations?.length" class="flow-card mt-[16rpx]">
                                    <text class="block text-[22rpx] font-bold text-[#9CA3AF] mb-[8rpx]">
                                        信息来源 · {{ research.citations.length }}
                                    </text>
                                    <view
                                        v-for="(cite, ci) in research.citations.slice(0, 5)"
                                        :key="ci"
                                        class="flex items-center gap-[14rpx] py-[10rpx]">
                                        <view
                                            class="w-[28rpx] h-[28rpx] rounded-[6rpx] bg-[#F3F4F6] flex-shrink-0 overflow-hidden">
                                            <image
                                                v-if="cite.logo_url"
                                                :src="cite.logo_url"
                                                mode="aspectFill"
                                                class="w-full h-full" />
                                        </view>
                                        <text class="flex-1 min-w-0 text-[22rpx] text-[#6B7280] line-clamp-1">
                                            {{ cite.title || cite.site_name || cite.url }}
                                        </text>
                                        <text
                                            v-if="cite.publish_time"
                                            class="text-[20rpx] text-[#D1D5DB] flex-shrink-0">
                                            {{ String(cite.publish_time).slice(0, 10) }}
                                        </text>
                                    </view>
                                </view>
                            </template>

                            <!-- 平台热度洞察（抖音） -->
                            <view v-if="insight" class="flow-card mt-[16rpx]">
                                <view class="flex items-center justify-between">
                                    <text class="text-[26rpx] font-bold text-[#111827]">平台热度洞察</text>
                                    <text class="text-[20rpx] text-[#D1D5DB]">来自抖音热点数据</text>
                                </view>
                                <view v-if="trendBars.length" class="mt-[16rpx]">
                                    <view class="flex items-end gap-[4rpx] h-[88rpx]">
                                        <view
                                            v-for="(bar, bi) in trendBars"
                                            :key="bi"
                                            class="flex-1 rounded-t-[4rpx] bg-[#BFDBFE]"
                                            :style="`height:${bar}%`"></view>
                                    </view>
                                    <view class="flex justify-between mt-[6rpx]">
                                        <text class="text-[20rpx] text-[#D1D5DB]">{{ trendRange.start }}</text>
                                        <text class="text-[20rpx] text-[#D1D5DB]">热度趋势</text>
                                        <text class="text-[20rpx] text-[#D1D5DB]">{{ trendRange.end }}</text>
                                    </view>
                                </view>
                                <view
                                    v-if="insight.gender?.length"
                                    class="mt-[20rpx] pt-[20rpx] border-[0] border-t-[2rpx] border-solid border-[#F3F4F8]">
                                    <text class="block text-[22rpx] font-bold text-[#9CA3AF] mb-[12rpx]">人群画像</text>
                                    <view class="flex items-center gap-[14rpx]">
                                        <text class="text-[22rpx] text-primary font-semibold flex-shrink-0">
                                            男 {{ malePercent }}%
                                        </text>
                                        <view class="flex-1 h-[14rpx] rounded-full overflow-hidden flex">
                                            <view :style="`width:${malePercent}%;background:#3B82F6`"></view>
                                            <view class="flex-1" style="background: #f472b6"></view>
                                        </view>
                                        <text class="text-[22rpx] font-semibold flex-shrink-0" style="color: #ec4899">
                                            女 {{ 100 - malePercent }}%
                                        </text>
                                    </view>
                                    <view class="flex items-center gap-[10rpx] mt-[14rpx] flex-wrap">
                                        <text
                                            v-for="age in ageTop"
                                            :key="'age-' + age.name"
                                            class="px-[14rpx] py-[4rpx] rounded-[8rpx] bg-[#EFF6FF] text-primary text-[20rpx]">
                                            {{ age.name }}岁 {{ toPercent(age.value) }}%
                                        </text>
                                        <text
                                            v-for="prov in provinceTop"
                                            :key="'prov-' + prov.name"
                                            class="px-[14rpx] py-[4rpx] rounded-[8rpx] bg-[#F3F4F6] text-[#6B7280] text-[20rpx]">
                                            {{ prov.name }}
                                        </text>
                                    </view>
                                </view>
                                <view
                                    v-if="insight.videos?.length"
                                    class="mt-[20rpx] pt-[20rpx] border-[0] border-t-[2rpx] border-solid border-[#F3F4F8]">
                                    <text class="block text-[22rpx] font-bold text-[#9CA3AF] mb-[12rpx]">
                                        大家都怎么做这个热点 · {{ insight.videos.length }}
                                    </text>
                                    <scroll-view scroll-x class="w-full" :show-scrollbar="false">
                                        <view class="inline-flex gap-[16rpx] pb-[6rpx]">
                                            <view
                                                v-for="(video, vi) in insight.videos.slice(0, 8)"
                                                :key="vi"
                                                class="w-[172rpx] flex-shrink-0"
                                                @click="previewInsightVideo(video)">
                                                <view
                                                    class="rounded-[14rpx] overflow-hidden bg-[#F3F4F6]"
                                                    style="height: 228rpx">
                                                    <image
                                                        v-if="video.cover"
                                                        :src="video.cover"
                                                        mode="aspectFill"
                                                        class="w-full h-full"
                                                        lazy-load />
                                                </view>
                                                <text
                                                    class="block text-[20rpx] text-[#4B5563] leading-snug mt-[6rpx] line-clamp-2">
                                                    {{ video.title }}
                                                </text>
                                                <text class="block text-[20rpx] text-[#D1D5DB] mt-[2rpx]">
                                                    ❤ {{ formatDigg(video.digg_cnt) }}
                                                </text>
                                            </view>
                                        </view>
                                    </scroll-view>
                                </view>
                            </view>
                            <view v-else-if="insightBusy" class="flow-card mt-[16rpx] flex items-center gap-[16rpx]">
                                <u-loading mode="circle" size="28" color="#60A5FA"></u-loading>
                                <text class="text-[24rpx] text-[#9CA3AF]">正在拉取平台热度洞察…</text>
                            </view>

                            <!-- 步骤 2 选择人设 -->
                            <view class="flex items-center gap-[12rpx] mt-[32rpx] mb-[16rpx]">
                                <view class="step-no" :class="research ? 'bg-primary' : 'bg-[#D1D5DB]'">
                                    <text class="text-white">2</text>
                                </view>
                                <text class="text-[29rpx] font-bold text-[#111827]">选择人设</text>
                            </view>
                            <view v-if="!research" class="flow-card">
                                <text class="text-[25rpx] text-[#D1D5DB]">搜索完成后可选</text>
                            </view>
                            <template v-else>
                                <view v-if="!personas.length" class="flow-card">
                                    <text class="text-[25rpx] text-[#9CA3AF]">
                                        还没有可用人设，请先到「AI 数字员工」创建人设
                                    </text>
                                </view>
                                <view v-else class="grid grid-cols-2 gap-[16rpx]">
                                    <view
                                        v-for="persona in personas"
                                        :key="persona.id"
                                        class="flow-card flex items-center gap-[18rpx] border-[3rpx] border-solid"
                                        :class="personaId === persona.id ? 'border-primary' : 'border-[transparent]'"
                                        @click="pickPersona(persona.id)">
                                        <image
                                            :src="persona.avatar"
                                            mode="aspectFill"
                                            class="w-[72rpx] h-[72rpx] rounded-[14rpx] bg-[#F3F4F6] flex-shrink-0" />
                                        <view class="flex-1 min-w-0">
                                            <text class="block text-[26rpx] font-bold text-[#111827] line-clamp-1">
                                                {{ persona.name }}
                                            </text>
                                            <text class="block text-[20rpx] text-[#9CA3AF] mt-[4rpx] line-clamp-1">
                                                {{ persona.tag || "" }}
                                            </text>
                                        </view>
                                    </view>
                                </view>

                                <view v-if="analyzing" class="flow-card mt-[16rpx] flex items-center gap-[20rpx]">
                                    <u-loading mode="circle" size="40" color="#0065fb"></u-loading>
                                    <view>
                                        <text class="block text-sm font-semibold text-primary">
                                            正在分析热点与人设的结合点…
                                        </text>
                                        <text class="block text-[22rpx] text-[#9CA3AF] mt-[4rpx]">
                                            判断契合度、切入方式和风险
                                        </text>
                                    </view>
                                </view>
                                <view v-else-if="analyzeErr" class="err-box mt-[16rpx]">
                                    <view class="flex-shrink-0 mt-[2rpx]">
                                        <u-icon name="error-circle" color="#EF4444" :size="26"></u-icon>
                                    </view>
                                    <text class="flex-1 text-xs text-[#EF4444] leading-relaxed">{{ analyzeErr }}</text>
                                </view>
                                <view v-else-if="analysis" class="flow-card mt-[16rpx]">
                                    <view class="flex items-center justify-between">
                                        <text class="text-[26rpx] font-bold text-[#111827]">热点 × 人设 结合分析</text>
                                        <text class="text-[24rpx] font-bold" :class="fitTextClass">
                                            {{ fitLevelText }} {{ analysis.fit_score }}
                                        </text>
                                    </view>
                                    <view class="h-[12rpx] bg-[#F3F4F6] rounded-full mt-[14rpx] overflow-hidden">
                                        <view
                                            class="h-full rounded-full"
                                            :class="fitBarClass"
                                            :style="`width:${analysis.fit_score}%`"></view>
                                    </view>
                                    <text
                                        v-if="analysis.fit_reason"
                                        class="block text-[24rpx] text-[#6B7280] leading-relaxed mt-[16rpx] break-all">
                                        {{ analysis.fit_reason }}
                                    </text>
                                    <template v-if="analysis.hooks?.length">
                                        <text class="block text-[22rpx] font-bold text-[#9CA3AF] mt-[22rpx] mb-[10rpx]">
                                            可以这么切
                                        </text>
                                        <view
                                            v-for="(hook, hi) in analysis.hooks"
                                            :key="hi"
                                            class="bg-[#F9FAFB] rounded-[16rpx] p-[18rpx] mb-[10rpx]">
                                            <text class="block text-[25rpx] font-semibold text-primary">
                                                {{ hook.label }}
                                            </text>
                                            <text
                                                class="block text-[24rpx] text-[#6B7280] leading-relaxed mt-[6rpx] break-all">
                                                {{ hook.detail }}
                                            </text>
                                        </view>
                                    </template>
                                    <view
                                        v-if="analysis.risks?.length"
                                        class="flex items-start gap-[10rpx] px-[20rpx] py-[16rpx] rounded-[16rpx] mt-[12rpx] bg-[#FFFBEB] border-[2rpx] border-solid border-[#FDE68A]">
                                        <u-icon
                                            name="warning"
                                            color="#F59E0B"
                                            :size="26"
                                            class="flex-shrink-0"></u-icon>
                                        <view class="flex-1 min-w-0">
                                            <text
                                                v-for="(risk, ri) in analysis.risks"
                                                :key="ri"
                                                class="block text-xs text-[#D97706] leading-relaxed break-all">
                                                {{ risk }}
                                            </text>
                                        </view>
                                    </view>
                                </view>
                            </template>

                            <!-- 步骤 3 高级设置 -->
                            <view class="flex items-center gap-[12rpx] mt-[32rpx] mb-[16rpx]">
                                <view class="step-no" :class="analysis ? 'bg-primary' : 'bg-[#D1D5DB]'">
                                    <text class="text-white">3</text>
                                </view>
                                <text class="text-[29rpx] font-bold text-[#111827]">高级设置</text>
                                <view
                                    v-if="analysis"
                                    class="ml-auto flex items-center gap-[6rpx] bg-[#E9EBF0] rounded-full px-[18rpx] py-[6rpx]"
                                    @click="showAdv = !showAdv">
                                    <text class="text-[20rpx] text-[#6B7280]">{{ showAdv ? "收起" : "展开" }}</text>
                                    <u-icon
                                        :name="showAdv ? 'arrow-up' : 'arrow-down'"
                                        color="#6B7280"
                                        :size="20"></u-icon>
                                </view>
                            </view>
                            <view v-if="!analysis" class="flow-card">
                                <text class="text-[25rpx] text-[#D1D5DB]">选好人设、分析完成后可配置</text>
                            </view>
                            <view
                                v-else-if="!showAdv"
                                class="flow-card flex items-center gap-[10rpx] flex-wrap"
                                @click="showAdv = true">
                                <text
                                    class="px-[14rpx] py-[6rpx] rounded-[8rpx] bg-[#EFF6FF] text-primary text-[20rpx] font-semibold">
                                    {{ goalLabel(opt.goal) }}
                                </text>
                                <text
                                    class="px-[14rpx] py-[6rpx] rounded-[8rpx] bg-[#F5F3FF] text-[#7C3AED] text-[20rpx] font-semibold">
                                    {{ videoTypeLabel(opt.video_type)
                                    }}{{ opt.video_type === "digital" && opt.avatar ? " · " + opt.avatar : "" }}
                                </text>
                                <text
                                    class="px-[14rpx] py-[6rpx] rounded-[8rpx] bg-[#F3F4F6] text-[#6B7280] text-[20rpx]">
                                    {{ opt.direction }}
                                </text>
                                <text
                                    class="px-[14rpx] py-[6rpx] rounded-[8rpx] bg-[#F3F4F6] text-[#6B7280] text-[20rpx]">
                                    {{ materialLabel(opt.material_mode) }}
                                </text>
                                <text
                                    class="px-[14rpx] py-[6rpx] rounded-[8rpx] bg-[#F3F4F6] text-[#6B7280] text-[20rpx]">
                                    {{ opt.duration_sec }} 秒
                                </text>
                                <text
                                    v-if="submittedMaterials.length"
                                    class="px-[14rpx] py-[6rpx] rounded-[8rpx] bg-[#F3F4F6] text-[#6B7280] text-[20rpx]">
                                    素材 {{ submittedMaterials.length }}
                                </text>
                            </view>
                            <view v-else class="flow-card">
                                <text class="adv-label">视频类型</text>
                                <view class="grid grid-cols-2 gap-[16rpx]">
                                    <view
                                        v-for="vt in videoTypeOptions"
                                        :key="vt.key"
                                        class="rounded-[16rpx] border-[3rpx] border-solid p-[20rpx]"
                                        :class="
                                            opt.video_type === vt.key
                                                ? 'border-primary bg-[#EFF6FF]'
                                                : 'border-[#F3F4F6] bg-[#F9FAFB]'
                                        "
                                        @click="opt.video_type = vt.key">
                                        <view class="flex items-center gap-[10rpx]">
                                            <image :src="vt.icon" mode="aspectFit" class="w-[30rpx] h-[30rpx]" />
                                            <text
                                                class="text-[25rpx] font-bold"
                                                :class="opt.video_type === vt.key ? 'text-primary' : 'text-[#6B7280]'">
                                                {{ vt.label }}
                                            </text>
                                        </view>
                                        <text
                                            class="block text-[20rpx] mt-[8rpx] leading-snug"
                                            :class="opt.video_type === vt.key ? 'text-[#60A5FA]' : 'text-[#9CA3AF]'">
                                            {{ vt.desc }}
                                        </text>
                                    </view>
                                </view>

                                <template v-if="opt.video_type === 'digital'">
                                    <view class="flex items-center gap-[10rpx] mt-[28rpx] mb-[14rpx]">
                                        <text class="text-[24rpx] font-bold text-[#374151]">数字人形象</text>
                                        <text
                                            class="text-[18rpx] font-semibold text-[#EF4444] bg-[#FEF2F2] px-[10rpx] py-[2rpx] rounded-[6rpx]">
                                            必选
                                        </text>
                                    </view>
                                    <view v-if="avatarLoading" class="flex items-center gap-[12rpx] py-[16rpx]">
                                        <u-loading mode="circle" size="26" color="#0065fb"></u-loading>
                                        <text class="text-[22rpx] text-[#9CA3AF]">加载形象中…</text>
                                    </view>
                                    <text v-else-if="!avatarList.length" class="text-[22rpx] text-[#9CA3AF]">
                                        该人设暂无可用数字人形象，请先到人设中心创建
                                    </text>
                                    <scroll-view v-else scroll-x class="w-full" :show-scrollbar="false">
                                        <view class="inline-flex gap-[16rpx] pb-[6rpx]">
                                            <view
                                                v-for="avatar in avatarList"
                                                :key="avatar.id"
                                                class="w-[144rpx] flex-shrink-0 rounded-[16rpx] border-[3rpx] border-solid p-[8rpx]"
                                                :class="
                                                    opt.avatar_id === avatar.id
                                                        ? 'border-primary bg-[#EFF6FF]'
                                                        : 'border-[#F3F4F6]'
                                                "
                                                @click="pickAvatar(avatar)">
                                                <image
                                                    :src="avatar.img"
                                                    mode="aspectFill"
                                                    class="w-full h-[128rpx] rounded-[12rpx] bg-[#F3F4F6]" />
                                                <text
                                                    class="block text-[22rpx] mt-[6rpx] text-center line-clamp-1"
                                                    :class="
                                                        opt.avatar_id === avatar.id
                                                            ? 'text-primary font-bold'
                                                            : 'text-[#6B7280]'
                                                    ">
                                                    {{ avatar.name }}
                                                </text>
                                            </view>
                                        </view>
                                    </scroll-view>
                                </template>

                                <text class="adv-label mt-[28rpx]">素材来源</text>
                                <view class="flex items-center bg-[#F3F4F6] rounded-full p-[6rpx]">
                                    <text
                                        v-for="material in optionsData.materials"
                                        :key="material.key"
                                        class="flex-1 py-[12rpx] rounded-full text-[22rpx] text-center line-clamp-1 break-all"
                                        :class="
                                            opt.material_mode === material.key
                                                ? 'bg-white text-primary font-semibold'
                                                : 'text-[#9CA3AF]'
                                        "
                                        @click="opt.material_mode = material.key">
                                        {{ material.label }}
                                    </text>
                                </view>
                                <view
                                    v-if="opt.material_mode !== 'persona'"
                                    class="inline-flex items-center gap-[10rpx] mt-[16rpx] px-[20rpx] py-[10rpx] rounded-full border-[2rpx] border-solid border-[#FDE68A] bg-[#FFFBEB]">
                                    <u-icon name="warning" color="#F59E0B" :size="24"></u-icon>
                                    <text class="text-xs text-[#D97706]">需额外消耗算力</text>
                                </view>

                                <template v-if="opt.material_mode !== 'ai'">
                                    <view class="flex items-center gap-[10rpx] mt-[28rpx] mb-[14rpx] flex-wrap">
                                        <text class="text-[24rpx] font-bold text-[#374151]">混剪素材</text>
                                        <text
                                            v-if="materialRequired"
                                            class="text-[18rpx] font-semibold text-[#EF4444] bg-[#FEF2F2] px-[10rpx] py-[2rpx] rounded-[6rpx]">
                                            必选
                                        </text>
                                        <text v-else class="text-[18rpx] text-[#D1D5DB]">选填 · 口播时穿插展示</text>
                                        <text class="text-[18rpx] text-[#D1D5DB]">
                                            限{{ montageConfig.materialTotalDuration }}分钟
                                        </text>
                                        <text
                                            v-if="opt.materials.length"
                                            class="text-[18rpx] font-semibold"
                                            :class="
                                                selectedMaterialDuration > MATERIAL_DURATION_LIMIT
                                                    ? 'text-[#EF4444]'
                                                    : 'text-primary'
                                            ">
                                            已选 {{ opt.materials.length }} · {{ selectedMaterialDuration }}/{{
                                                MATERIAL_DURATION_LIMIT
                                            }}秒
                                        </text>
                                    </view>
                                    <view v-if="materialLoading" class="flex items-center gap-[12rpx] py-[16rpx]">
                                        <u-loading mode="circle" size="26" color="#0065fb"></u-loading>
                                        <text class="text-[22rpx] text-[#9CA3AF]">加载素材中…</text>
                                    </view>
                                    <text v-else-if="!materialList.length" class="text-[22rpx] text-[#9CA3AF]">
                                        该人设暂无可用素材，可在人设素材库上传
                                    </text>
                                    <template v-else>
                                        <view class="grid grid-cols-3 gap-[16rpx]">
                                            <view
                                                v-for="material in materialList"
                                                :key="material.id"
                                                class="relative rounded-[16rpx] overflow-hidden border-[3rpx] border-solid"
                                                :class="
                                                    opt.materials.includes(material.id)
                                                        ? 'border-primary'
                                                        : 'border-[transparent]'
                                                "
                                                @click="toggleMaterial(material.id)">
                                                <view class="relative w-full h-[112rpx] bg-[#F3F4F6]">
                                                    <image
                                                        :src="material.img"
                                                        mode="aspectFill"
                                                        lazy-load
                                                        class="w-full h-full" />
                                                    <view
                                                        class="absolute top-[6rpx] left-[6rpx] px-[8rpx] py-[2rpx] rounded-[6rpx]"
                                                        style="background: rgba(0, 0, 0, 0.55)">
                                                        <text class="text-[16rpx] text-white leading-none">
                                                            {{ material.material_type === 1 ? "视频" : "图片" }}
                                                        </text>
                                                    </view>
                                                    <view
                                                        v-if="material.material_type === 1"
                                                        class="absolute inset-0 flex items-center justify-center">
                                                        <view
                                                            class="w-[36rpx] h-[36rpx] rounded-full bg-[#ffffff]/30 border border-solid border-[#ffffff]/40 flex items-center justify-center">
                                                            <u-icon
                                                                name="play-right-fill"
                                                                color="#ffffff"
                                                                :size="14"
                                                                class="ml-[2rpx]"></u-icon>
                                                        </view>
                                                    </view>
                                                </view>
                                                <view
                                                    v-if="opt.materials.includes(material.id)"
                                                    class="absolute top-[8rpx] right-[8rpx] w-[32rpx] h-[32rpx] rounded-full bg-primary flex items-center justify-center">
                                                    <u-icon name="checkmark" color="#ffffff" :size="18"></u-icon>
                                                </view>
                                                <view class="bg-white py-[8rpx]">
                                                    <text
                                                        class="block text-[20rpx] text-center line-clamp-1 px-[8rpx]"
                                                        :class="
                                                            opt.materials.includes(material.id)
                                                                ? 'text-primary font-semibold'
                                                                : 'text-[#6B7280]'
                                                        ">
                                                        {{ material.name }}
                                                    </text>
                                                </view>
                                            </view>
                                        </view>
                                        <view
                                            v-if="!materialFinished"
                                            class="mt-[16rpx] py-[16rpx] rounded-[16rpx] bg-[#F9FAFB] flex items-center justify-center gap-[10rpx] active:opacity-70"
                                            @click="loadMoreMaterials">
                                            <u-loading
                                                v-if="materialLoadingMore"
                                                mode="circle"
                                                size="24"
                                                color="#0065fb"></u-loading>
                                            <text
                                                class="text-[22rpx]"
                                                :class="materialLoadingMore ? 'text-[#9CA3AF]' : 'text-primary'">
                                                {{ materialLoadingMore ? "加载中…" : "加载更多素材" }}
                                            </text>
                                            <u-icon
                                                v-if="!materialLoadingMore"
                                                name="arrow-down"
                                                color="#0065fb"
                                                :size="22"></u-icon>
                                        </view>
                                        <view
                                            v-else-if="materialPage > 1"
                                            class="mt-[16rpx] flex items-center justify-center">
                                            <text class="text-[20rpx] text-[#C4C9D4]">已加载全部素材</text>
                                        </view>
                                    </template>
                                </template>

                                <text class="adv-label mt-[28rpx]">最终目的</text>
                                <view class="flex flex-wrap gap-[12rpx]">
                                    <text
                                        v-for="goal in optionsData.goals"
                                        :key="goal.key"
                                        class="px-[22rpx] py-[12rpx] rounded-[12rpx] text-[24rpx]"
                                        :class="
                                            opt.goal === goal.key
                                                ? 'bg-primary text-white font-semibold'
                                                : 'bg-[#F3F4F6] text-[#6B7280]'
                                        "
                                        @click="opt.goal = goal.key">
                                        {{ goal.label }}
                                    </text>
                                </view>
                                <text
                                    v-if="analysis?.recommended_goal"
                                    class="block text-[20rpx] text-[#9CA3AF] mt-[10rpx]">
                                    AI 建议：{{ goalLabel(analysis.recommended_goal) }}
                                </text>
                                <view
                                    v-if="opt.goal === 'sell'"
                                    class="bg-[#F9FAFB] rounded-[16rpx] p-[20rpx] mt-[16rpx]">
                                    <input
                                        v-model="opt.product"
                                        :maxlength="100"
                                        placeholder="要推的产品 / 卖点（如：99元AI入门课）"
                                        placeholder-style="color:#9CA3AF"
                                        class="w-full text-[25rpx] text-[#1F2937]" />
                                </view>

                                <text class="adv-label mt-[28rpx]">内容方向</text>
                                <view class="flex flex-wrap gap-[12rpx]">
                                    <text
                                        v-for="direction in optionsData.directions"
                                        :key="direction"
                                        class="px-[22rpx] py-[12rpx] rounded-[12rpx] text-[24rpx]"
                                        :class="
                                            opt.direction === direction
                                                ? 'bg-primary text-white font-semibold'
                                                : 'bg-[#F3F4F6] text-[#6B7280]'
                                        "
                                        @click="opt.direction = direction">
                                        {{ direction }}
                                    </text>
                                </view>

                                <text class="adv-label mt-[28rpx]">口播时长</text>
                                <view class="flex items-center bg-[#F3F4F6] rounded-full p-[6rpx]">
                                    <text
                                        v-for="duration in optionsData.durations"
                                        :key="duration"
                                        class="flex-1 py-[12rpx] rounded-full text-[22rpx] text-center"
                                        :class="
                                            opt.duration_sec === duration
                                                ? 'bg-white text-primary font-semibold'
                                                : 'text-[#9CA3AF]'
                                        "
                                        @click="opt.duration_sec = duration">
                                        {{ duration }} 秒
                                    </text>
                                </view>

                                <view class="flex items-center gap-[8rpx] mt-[28rpx] mb-[14rpx]">
                                    <text class="text-[24rpx] font-bold text-[#374151]">结尾引导语</text>
                                    <text class="text-[20rpx] text-[#D1D5DB]">选填</text>
                                </view>
                                <view class="bg-[#F9FAFB] rounded-[16rpx] p-[20rpx]">
                                    <input
                                        v-model="opt.cta"
                                        :maxlength="100"
                                        placeholder="留空则由 AI 按目的自动生成"
                                        placeholder-style="color:#9CA3AF"
                                        class="w-full text-[25rpx] text-[#1F2937]" />
                                </view>
                            </view>

                            <!-- 步骤 4 口播文案 -->
                            <view class="flex items-center gap-[12rpx] mt-[32rpx] mb-[16rpx]">
                                <view class="step-no" :class="script ? 'bg-primary' : 'bg-[#D1D5DB]'">
                                    <text class="text-white">4</text>
                                </view>
                                <text class="text-[29rpx] font-bold text-[#111827]">口播文案</text>
                            </view>
                            <!-- 错误独立展示，不顶掉已生成的文案卡（旧文案仍可查看/提交） -->
                            <view v-if="scriptErr" class="err-box mb-[12rpx]">
                                <u-icon name="error-circle" color="#EF4444" :size="26"></u-icon>
                                <text class="flex-1 text-xs text-[#EF4444] leading-relaxed">{{ scriptErr }}</text>
                            </view>
                            <view v-if="scriptBusy && !script" class="flow-card flex items-center gap-[20rpx]">
                                <u-loading mode="circle" size="40" color="#0065fb"></u-loading>
                                <view>
                                    <text class="block text-sm font-semibold text-primary">正在生成口播文案…</text>
                                    <text class="block text-[22rpx] text-[#9CA3AF] mt-[4rpx]">
                                        按「{{ goalLabel(opt.goal) }}」的落点和人设口吻写
                                    </text>
                                </view>
                            </view>
                            <view v-else-if="!script" class="flow-card">
                                <text class="text-[25rpx] text-[#D1D5DB]">确认设置后点下方按钮生成</text>
                            </view>
                            <view v-else class="flow-card">
                                <view class="bg-[#F9FAFB] rounded-[16rpx] p-[20rpx]">
                                    <textarea
                                        v-model="editedScript"
                                        :maxlength="20000"
                                        placeholder="口播文案"
                                        placeholder-style="color:#C0C4CC;font-size:26rpx;"
                                        class="w-full text-[27rpx] text-[#1F2937] leading-relaxed" />
                                </view>
                                <view class="flex items-center justify-between mt-[14rpx]">
                                    <text class="text-[22rpx] text-[#9CA3AF]">
                                        {{ scriptWordCount }} 字 · 约 {{ scriptEstSec }} 秒
                                    </text>
                                    <!-- 重新生成时旧文案仍展示，busy 态靠按钮自身反馈 -->
                                    <view class="flex items-center gap-[8rpx]" @click="runScript">
                                        <u-loading
                                            v-if="scriptBusy"
                                            mode="circle"
                                            size="24"
                                            color="#0065fb"></u-loading>
                                        <text
                                            class="text-[24rpx] font-semibold"
                                            :class="scriptBusy ? 'text-[#9CA3AF]' : 'text-primary'">
                                            {{ scriptBusy ? "正在重新生成…" : "重新生成" }}
                                        </text>
                                    </view>
                                </view>
                                <view v-if="script.hashtags?.length" class="flex flex-wrap gap-[10rpx] mt-[16rpx]">
                                    <text
                                        v-for="tag in script.hashtags"
                                        :key="tag"
                                        class="px-[14rpx] py-[4rpx] rounded-[8rpx] bg-[#EFF6FF] text-primary text-[20rpx]">
                                        #{{ tag }}
                                    </text>
                                </view>
                                <template v-if="script.shots?.length">
                                    <text class="block text-[22rpx] font-bold text-[#9CA3AF] mt-[22rpx] mb-[8rpx]">
                                        画面建议 · {{ materialLabel(script.material_mode || opt.material_mode) }}
                                    </text>
                                    <view
                                        v-for="(shot, si) in script.shots"
                                        :key="si"
                                        class="flex gap-[14rpx] py-[6rpx]">
                                        <text class="text-[20rpx] text-[#D1D5DB] font-bold flex-shrink-0 mt-[2rpx]">
                                            {{ si + 1 }}
                                        </text>
                                        <text class="flex-1 text-[24rpx] text-[#6B7280] leading-relaxed break-all">
                                            {{ shot }}
                                        </text>
                                    </view>
                                </template>
                            </view>
                        </view>
                    </scroll-view>
                </view>

                <!-- 底部主按钮 -->
                <view
                    class="px-[32rpx] pt-[16rpx] pb-[calc(20rpx+env(safe-area-inset-bottom))] bg-white border-[0] border-t-[2rpx] border-solid border-[#F3F4F6]">
                    <button
                        class="plain-btn w-full py-[24rpx] rounded-full flex items-center justify-center gap-[16rpx]"
                        :class="mainBtn.disabled || mainBtn.gate ? 'bg-[#C3D4EE]' : 'cta-gradient'"
                        :disabled="mainBtn.disabled"
                        @click="handleMainAction">
                        <text
                            class="text-[30rpx] font-semibold"
                            :class="mainBtn.disabled || mainBtn.gate ? 'text-[#5C7299]' : 'text-white'">
                            {{ mainBtn.text }}
                        </text>
                        <view
                            v-if="mainBtn.cost"
                            class="rounded-full px-[18rpx] py-[4rpx]"
                            style="background: rgba(255, 255, 255, 0.2)">
                            <text class="text-xs text-white">{{ mainBtn.cost }}</text>
                        </view>
                    </button>
                </view>
            </view>
        </template>
    </popup-bottom>
</template>

<script setup lang="ts">
import {
    getHotspotAvatars,
    getHotspotClipMaterials,
    hotspotAnalyze,
    hotspotResearch,
    hotspotScript,
    addHotspotTask,
    getHotspotInsight,
    getHotspotLastFlow,
} from "@/api/hotspot";
import { HotspotPlatform, HotspotVideoType, platformLabel, type HotspotOptionsData } from "@/ai_modules/hotspot/enums";
import { montageConfig } from "@/ai_modules/digital_human/config";
import IconDigitalBlue from "@/ai_modules/hotspot/static/icons/video_digital_blue.svg";
import IconClipsBlue from "@/ai_modules/hotspot/static/icons/video_clips_blue.svg";

interface HotTopic {
    title: string;
    platform: string;
    category?: string;
    rank?: number;
    heat_text?: string;
    days_on_board?: number;
    rank_diff?: number;
    /** 后端按分析记录标记，已分析的热点可从服务端还原上次现场 */
    analyzed?: boolean;
}

const props = defineProps<{
    modelValue: boolean;
    topic: HotTopic | null;
    periodLabel: string;
    personas: Record<string, any>[];
    optionsData: HotspotOptionsData;
}>();

const emit = defineEmits<{
    (e: "update:modelValue", v: boolean): void;
    (e: "created", task: Record<string, any>, navigate: boolean): void;
    (e: "analyzed", topic: HotTopic): void;
}>();

/** 分析记录持久缓存：点开过的热点把搜索/洞察/分析/设置/文案整体存下来，
 *  再进直接还原到上次停留步骤（跨页面、跨启动有效），不重跑收费的搜索/分析 */
const cacheKey = (topic: HotTopic | null) => (topic ? `${topic.platform}|${topic.title}` : "");

const readFlowCacheAll = (): Record<string, any> => {
    try {
        const all = uni.getStorageSync(FLOW_CACHE_KEY);
        return all && typeof all === "object" ? all : {};
    } catch {
        return {};
    }
};

/** 搜索完成后才值得存；各步骤成功与关闭弹窗时调用，LRU 保留最近 N 条 */
const saveFlowSnapshot = () => {
    const key = cacheKey(props.topic);
    if (!key || !research.value) return;
    const all = readFlowCacheAll();
    all[key] = {
        savedAt: Date.now(),
        research: research.value,
        insight: insight.value,
        personaId: personaId.value,
        analysis: analysis.value,
        opt: { ...opt, materials: [...opt.materials] },
        script: script.value,
        editedScript: editedScript.value,
    };
    const keys = Object.keys(all);
    if (keys.length > FLOW_CACHE_LIMIT) {
        keys.sort((a, b) => (all[a]?.savedAt || 0) - (all[b]?.savedAt || 0));
        keys.slice(0, keys.length - FLOW_CACHE_LIMIT).forEach((k) => delete all[k]);
    }
    try {
        uni.setStorageSync(FLOW_CACHE_KEY, all);
    } catch {
        // 存储超限：只保留当前一条重试，再失败放弃缓存
        try {
            uni.setStorageSync(FLOW_CACHE_KEY, { [key]: all[key] });
        } catch {
            /* 放弃缓存，不影响主流程 */
        }
    }
};

const removeFlowCacheEntry = () => {
    const key = cacheKey(props.topic);
    if (!key) return;
    const all = readFlowCacheAll();
    if (all[key]) {
        delete all[key];
        try {
            uni.setStorageSync(FLOW_CACHE_KEY, all);
        } catch {
            /* 忽略 */
        }
    }
};

/** 还原是「回放」而非用户改设置：抑制「目的/方向等变更即作废文案」的 watch，
 *  否则 Object.assign(opt) 触发 watch 会把刚还原的文案清空（返回再进入后文案丢失的根因） */
let suppressScriptReset = false;
/** 当前文案末尾已带的引导语（生成/还原时记录），引导语输入框改动后据此替换文案结尾，保持同步 */
let syncedCta = "";
const syncCtaIntoScript = (nextCta: string) => {
    const next = nextCta.trim();
    if (next === syncedCta) return;
    let body = editedScript.value.replace(/\s+$/, "");
    if (syncedCta) {
        // AI 可能在引导语后补了标点，匹配时一并去掉
        const escaped = syncedCta.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
        body = body.replace(new RegExp(`\\s*${escaped}[\\s。！!.～~]*$`), "");
    }
    editedScript.value = next ? (body ? `${body}\n${next}` : next) : body;
    syncedCta = next;
};
const withScriptResetSuppressed = (restore: () => void) => {
    suppressScriptReset = true;
    try {
        restore();
    } finally {
        // watch 在同一 tick 的 flush 阶段执行，nextTick 后释放才能盖住本次变更
        nextTick(() => {
            suppressScriptReset = false;
        });
    }
};

/** 还原上次记录：直接回到上次停留的步骤 */
const restoreFlowFromCache = (cached: Record<string, any>) => {
    withScriptResetSuppressed(() => {
        research.value = cached.research || null;
        insight.value = cached.insight || null;
        personaId.value = cached.personaId || null;
        analysis.value = cached.analysis || null;
        script.value = cached.script || null;
        editedScript.value = String(cached.editedScript ?? cached.script?.script ?? "");
        syncedCta = String(cached.opt?.cta || "").trim();
        if (cached.opt && typeof cached.opt === "object") {
            Object.assign(opt, cached.opt, {
                materials: Array.isArray(cached.opt.materials) ? [...cached.opt.materials] : [],
            });
        }
    });
    fromCache.value = true;
    cachedAt.value = Number(cached.savedAt || 0);
    // 还原到第三步（已分析、未生成文案）时同样默认展开高级设置
    showAdv.value = !!analysis.value && !script.value;
    if (personaId.value) {
        // 资产列表要重新拉，但保留缓存里的形象/素材选择
        loadPersonaAssets(personaId.value, true);
    }
};

const formatCachedAt = (ts: number) => {
    if (!ts) return "";
    const d = new Date(ts);
    const pad = (n: number) => String(n).padStart(2, "0");
    return `${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
};

const asPlainObject = (value: any): Record<string, any> | null =>
    value && typeof value === "object" && !Array.isArray(value) && Object.keys(value).length ? value : null;

/** 本地缓存 miss 时，从服务端最近一次任务还原完整现场（不重跑收费的搜索/分析），并回写本地缓存 */
const restoreFlowFromServer = (data: Record<string, any>): boolean => {
    const corePoints = Array.isArray(data.core_points) ? data.core_points : [];
    const scriptText = String(data.script || "");
    if (!corePoints.length && !scriptText) return false;

    const options = asPlainObject(data.options);
    withScriptResetSuppressed(() => {
        research.value = {
            topic: data.topic || props.topic?.title || "",
            summary: "",
            core_points: corePoints,
            citations: Array.isArray(data.citations) ? data.citations : [],
        };
        analysis.value = asPlainObject(data.analysis);
        const persona = asPlainObject(data.persona);
        personaId.value = Number(persona?.id) || null;
        if (options) {
            Object.assign(opt, options, {
                materials: Array.isArray(options.materials) ? options.materials.map(Number) : [],
                avatar_id: Number(options.avatar_id) || 0,
            });
        }
        if (scriptText) {
            script.value = {
                title: String(data.title || props.topic?.title || ""),
                script: scriptText,
                hashtags: Array.isArray(options?.hashtags) ? options?.hashtags : [],
                shots: Array.isArray(options?.shots) ? options?.shots : [],
                material_mode: String(options?.material_mode || opt.material_mode || ""),
            };
            editedScript.value = scriptText;
            syncedCta = String(options?.cta || "").trim();
        }
    });
    fromCache.value = true;
    const createdTs = Date.parse(String(data.created_at || "").replace(/-/g, "/"));
    cachedAt.value = Number.isFinite(createdTs) ? createdTs : 0;
    showAdv.value = !!analysis.value && !script.value;
    if (personaId.value) {
        loadPersonaAssets(personaId.value, true);
    }
    saveFlowSnapshot();
    return true;
};

const show = computed({
    get: () => props.modelValue,
    set: (v: boolean) => emit("update:modelValue", v),
});

const CHARS_PER_SEC = 4.2;

/** 分析记录本地缓存：点开过的热点把搜索/洞察/分析/设置/文案整体存下来，再进直接还原不重跑 */
const FLOW_CACHE_KEY = "hotspot_flow_cache_v1";
const FLOW_CACHE_LIMIT = 15;

const research = ref<Record<string, any> | null>(null);
const researchErr = ref("");
const insight = ref<Record<string, any> | null>(null);
const insightBusy = ref(false);
const personaId = ref<number | null>(null);
const analyzing = ref(false);
const analysis = ref<Record<string, any> | null>(null);
const analyzeErr = ref("");
const showAdv = ref(false);
const script = ref<Record<string, any> | null>(null);
const scriptBusy = ref(false);
const scriptErr = ref("");
const editedScript = ref("");
const creating = ref(false);
const fromCache = ref(false);
const cachedAt = ref(0);

const MATERIAL_PAGE_SIZE = 30;

const avatarList = ref<{ id: number; name: string; img: string }[]>([]);
const avatarLoading = ref(false);
/** 素材总时长限制（秒），与混剪模块同一配置：视频按实际时长、图片按固定秒数/张 */
const MATERIAL_DURATION_LIMIT = Number(montageConfig.materialTotalDuration) * 60;

/** material_type：1=视频 2=图片（与后端 Material::MATERIAL_TYPE_* 一致） */
const materialList = ref<{ id: number; name: string; img: string; material_type: number; duration: number }[]>([]);
const materialLoading = ref(false);
const materialLoadingMore = ref(false);
const materialPage = ref(1);
const materialFinished = ref(false);

const opt = reactive({
    goal: "traffic",
    direction: "观点输出",
    material_mode: "ai_persona",
    duration_sec: 60,
    product: "",
    cta: "",
    video_type: HotspotVideoType.DIGITAL as string,
    avatar: "",
    avatar_id: 0,
    materials: [] as number[],
});

const videoTypeOptions = computed(() => [
    {
        key: HotspotVideoType.DIGITAL,
        label: videoTypeLabel(HotspotVideoType.DIGITAL),
        desc: "数字人出镜口播，可搭配素材画面",
        icon: IconDigitalBlue,
    },
    {
        key: HotspotVideoType.CLIPS,
        label: videoTypeLabel(HotspotVideoType.CLIPS),
        desc: "纯素材拼剪配音，无需数字人形象",
        icon: IconClipsBlue,
    },
]);

const subTitle = computed(() => {
    const topic = props.topic;
    if (!topic) return "—";
    const extra = topic.days_on_board
        ? ` · 上榜 ${topic.days_on_board} 天`
        : topic.heat_text
        ? ` · ${topic.heat_text}`
        : "";
    return `${platformLabel(topic.platform)} ${props.periodLabel} · 第 ${topic.rank || "-"} 名${extra}`;
});

const goalLabel = (key: string) => props.optionsData.goals.find((g) => g.key === key)?.label || key;
const materialLabel = (key: string) => props.optionsData.materials.find((m) => m.key === key)?.label || key;
const videoTypeLabel = (key: string) => props.optionsData.video_types.find((v) => v.key === key)?.label || key;

// ────────── 洞察展示 ──────────
const trendBars = computed(() => {
    const trend = (insight.value?.trend || []).slice(-24);
    if (trend.length < 3) return [] as number[];
    const values = trend.map((p: any) => Number(p.value) || 0);
    const min = Math.min(...values);
    const max = Math.max(...values);
    return values.map((v: number) => (max > min ? 12 + ((v - min) / (max - min)) * 88 : 50));
});
const trendRange = computed(() => {
    const trend = (insight.value?.trend || []).slice(-24);
    const pick = (i: number) => String(trend[i]?.time || "").slice(5);
    return { start: pick(0), end: pick(trend.length - 1) };
});
/** 画像比例归一：后端契约是 0-1 小数，若上游改传百分数（>1）按百分数处理 */
const toPercent = (value: number) => {
    const num = Number(value) || 0;
    return Math.max(0, Math.min(100, Math.round(num > 1 ? num : num * 100)));
};
const malePercent = computed(() => {
    const male = (insight.value?.gender || []).find((g: any) => g.name === "男")?.value || 0;
    return toPercent(male);
});
const ageTop = computed(() => [...(insight.value?.age || [])].sort((a: any, b: any) => b.value - a.value).slice(0, 3));
const provinceTop = computed(() =>
    [...(insight.value?.province || [])].sort((a: any, b: any) => b.value - a.value).slice(0, 3),
);
const formatDigg = (count: number) => (count >= 10000 ? (count / 10000).toFixed(1) + "万" : String(count || 0));
const previewInsightVideo = (video: any) => {
    if (!video?.cover) return;
    uni.previewImage({ urls: [video.cover] });
};

/** 画像摘要：喂给结合分析，受众判断从"猜"变成"对数据" */
const portraitSummary = () => {
    const ins = insight.value;
    if (!ins?.gender?.length) return "";
    const gender = ins.gender.map((x: any) => `${x.name}${toPercent(x.value)}%`).join(" ");
    const age = ageTop.value.map((x: any) => `${x.name}岁${toPercent(x.value)}%`).join(" ");
    const province = provinceTop.value.map((x: any) => x.name).join(" ");
    return `性别：${gender}；年龄：${age}；主要地域：${province}`;
};

// ────────── 契合度样式 ──────────
const fitScoreVal = computed(() => Number(analysis.value?.fit_score || 0));
const fitLevelText = computed(() =>
    fitScoreVal.value >= 70 ? "契合度高" : fitScoreVal.value >= 40 ? "有一定关联" : "关联较弱",
);
const fitTextClass = computed(() =>
    fitScoreVal.value >= 70 ? "text-[#16A34A]" : fitScoreVal.value >= 40 ? "text-[#D97706]" : "text-[#EF4444]",
);
const fitBarClass = computed(() =>
    fitScoreVal.value >= 70 ? "bg-[#22C55E]" : fitScoreVal.value >= 40 ? "bg-[#FBBF24]" : "bg-[#F87171]",
);

// ────────── 文案编辑统计 ──────────
const scriptWordCount = computed(() => editedScript.value.replace(/\s/g, "").length);
const scriptEstSec = computed(() => Math.max(1, Math.round(scriptWordCount.value / CHARS_PER_SEC)));

// ────────── 请求链 ──────────
let flowToken = 0;

const resetFlow = () => {
    flowToken += 1;
    research.value = null;
    researchErr.value = "";
    fromCache.value = false;
    insight.value = null;
    insightBusy.value = false;
    personaId.value = null;
    analyzing.value = false;
    analysis.value = null;
    analyzeErr.value = "";
    showAdv.value = false;
    script.value = null;
    scriptBusy.value = false;
    scriptErr.value = "";
    editedScript.value = "";
    syncedCta = "";
    creating.value = false;
    fromCache.value = false;
    cachedAt.value = 0;
    avatarList.value = [];
    materialList.value = [];
    materialLoadingMore.value = false;
    materialPage.value = 1;
    materialFinished.value = false;
    Object.assign(opt, {
        goal: "traffic",
        direction: "观点输出",
        material_mode: "ai_persona",
        duration_sec: 60,
        product: "",
        cta: "",
        video_type: HotspotVideoType.DIGITAL,
        avatar: "",
        avatar_id: 0,
        materials: [],
    });
};

const runResearch = async () => {
    const topic = props.topic;
    if (!topic) return;
    const token = flowToken;
    researchErr.value = "";
    research.value = null;
    try {
        const res = await hotspotResearch({
            topic: topic.title,
            platform: topic.platform,
            category: topic.category || "",
        });
        if (token !== flowToken) return;
        research.value = res;
        saveFlowSnapshot();
    } catch (error: any) {
        if (token !== flowToken) return;
        researchErr.value = String(error?.message || error || "联网搜索失败");
    }
};

/** 丢弃缓存记录，重新走搜索链 */
const reResearch = () => {
    removeFlowCacheEntry();
    resetFlow();
    runResearch();
    loadInsight();
};

const loadInsight = async () => {
    const topic = props.topic;
    if (!topic || topic.platform !== HotspotPlatform.DOUYIN) return;
    const token = flowToken;
    insightBusy.value = true;
    try {
        const res = await getHotspotInsight({ topic: topic.title });
        if (token !== flowToken) return;
        insight.value = res?.found ? res : null;
        saveFlowSnapshot();
    } catch {
        // 洞察是增强信息，失败静默
    } finally {
        if (token === flowToken) insightBusy.value = false;
    }
};

const pickPersona = async (id: number) => {
    if (analyzing.value || !props.topic || !research.value) return;
    const token = flowToken;
    personaId.value = id;
    analysis.value = null;
    analyzeErr.value = "";
    script.value = null;
    scriptErr.value = "";
    scriptBusy.value = false; // 在途的旧人设文案请求响应会被快照守卫丢弃，这里复位加载态
    analyzing.value = true;
    loadPersonaAssets(id);
    try {
        const res = await hotspotAnalyze({
            topic: props.topic.title,
            platform: props.topic.platform,
            summary: research.value.summary || "",
            core_points: research.value.core_points || [],
            persona: { id },
            portrait: portraitSummary(),
        });
        if (token !== flowToken || personaId.value !== id) return;
        analysis.value = res;
        if (res?.recommended_goal) opt.goal = res.recommended_goal;
        if (res?.recommended_direction) opt.direction = res.recommended_direction;
        // 分析完成即进入第三步，高级设置默认展开让用户直接配置必填项
        showAdv.value = true;
        saveFlowSnapshot();
        if (props.topic) emit("analyzed", props.topic);
    } catch (error: any) {
        if (token !== flowToken || personaId.value !== id) return;
        analyzeErr.value = String(error?.message || error || "结合分析失败");
    } finally {
        if (token === flowToken && personaId.value === id) analyzing.value = false;
    }
};

/** 选完人设后拉该人设的数字人形象与混剪素材（切换人设时重置已选与分页）
 *  preserveSelection：还原缓存记录时保留已选形象/素材，仅在形象已失效时清掉 */
const loadPersonaAssets = async (id: number, preserveSelection = false) => {
    const token = flowToken;
    if (!preserveSelection) {
        opt.avatar = "";
        opt.avatar_id = 0;
        opt.materials = [];
    }
    avatarLoading.value = true;
    materialLoading.value = true;
    materialLoadingMore.value = false;
    materialPage.value = 1;
    materialFinished.value = false;
    try {
        const list = await getHotspotAvatars({ persona_id: id });
        if (token === flowToken && personaId.value === id) {
            avatarList.value = Array.isArray(list) ? list : [];
            // 缓存里选的形象已被删除时清掉，避免带失效 id 提交
            if (preserveSelection && opt.avatar_id && !avatarList.value.some((a) => a.id === opt.avatar_id)) {
                opt.avatar_id = 0;
                opt.avatar = "";
            }
        }
    } catch {
        if (token === flowToken && personaId.value === id) avatarList.value = [];
    } finally {
        if (token === flowToken && personaId.value === id) avatarLoading.value = false;
    }
    await fetchMaterialPage(id, 1);
    if (token === flowToken && personaId.value === id) materialLoading.value = false;
};

/** 素材分页：接口不返回总数，按「返回条数 < 页大小」判定到底 */
const fetchMaterialPage = async (id: number, pageNo: number) => {
    const token = flowToken;
    try {
        const list = await getHotspotClipMaterials({
            persona_id: id,
            page_no: pageNo,
            page_size: MATERIAL_PAGE_SIZE,
        });
        if (token !== flowToken || personaId.value !== id) return;
        const rows = Array.isArray(list) ? list : [];
        materialList.value = pageNo === 1 ? rows : [...materialList.value, ...rows];
        materialPage.value = pageNo;
        materialFinished.value = rows.length < MATERIAL_PAGE_SIZE;
    } catch {
        if (token !== flowToken || personaId.value !== id) return;
        if (pageNo === 1) {
            materialList.value = [];
            materialFinished.value = true;
        }
        // 加载更多失败不置 finished，保留按钮可重试
    }
};

const loadMoreMaterials = async () => {
    if (materialLoadingMore.value || materialFinished.value || !personaId.value) return;
    materialLoadingMore.value = true;
    await fetchMaterialPage(personaId.value, materialPage.value + 1);
    materialLoadingMore.value = false;
};

const pickAvatar = (avatar: { id: number; name: string }) => {
    if (opt.avatar_id === avatar.id) {
        opt.avatar_id = 0;
        opt.avatar = "";
    } else {
        opt.avatar_id = avatar.id;
        opt.avatar = avatar.name;
    }
};

/** 单个素材折算时长（秒）：视频按实际时长、图片按固定秒数/张（与混剪模块同口径） */
const materialSeconds = (id: number): number => {
    const material = materialList.value.find((item) => item.id === id);
    if (!material) return 0;
    return material.material_type === 1 ? Number(material.duration) || 0 : Number(montageConfig.imageDuration) || 0;
};

/** 「AI找素材」下混剪素材区隐藏，已选素材只留在内存里、不参与提交/校验/展示，
 *  切回人设素材原样恢复：手滑切一下来源不该让用户重挑一遍素材 */
const submittedMaterials = computed(() => (opt.material_mode === "ai" ? [] : opt.materials));

/** 已选素材总时长（秒） */
const selectedMaterialDuration = computed(() =>
    submittedMaterials.value.reduce((total, id) => total + materialSeconds(id), 0),
);

const toggleMaterial = (id: number) => {
    const index = opt.materials.indexOf(id);
    if (index >= 0) {
        opt.materials.splice(index, 1);
        return;
    }
    if (selectedMaterialDuration.value + materialSeconds(id) > MATERIAL_DURATION_LIMIT) {
        uni.$u.toast(`素材总时长不能超过${montageConfig.materialTotalDuration}分钟，无法生成视频`);
        return;
    }
    opt.materials.push(id);
};

const buildOptions = () => ({
    goal: opt.goal,
    direction: opt.direction,
    material_mode: opt.material_mode,
    duration_sec: opt.duration_sec,
    product: opt.product.trim(),
    cta: opt.cta.trim(),
    video_type: opt.video_type,
    avatar: opt.avatar,
    avatar_id: opt.avatar_id,
    materials: submittedMaterials.value,
});

/** 只快照影响文案内容的设置：视频类型/形象/素材/素材来源只决定画面，
 *  且形象/素材会被资产异步回填改动，纳入快照会误报「设置已变更」白丢已扣费的文案 */
const scriptAffectingOptions = () => {
    const options = buildOptions();
    return JSON.stringify({
        goal: options.goal,
        direction: options.direction,
        duration_sec: options.duration_sec,
        product: options.product,
        cta: options.cta,
    });
};

const runScript = async () => {
    if (scriptBusy.value || !props.topic || !research.value || !personaId.value) return;
    const token = flowToken;
    // 快照发起时的人设与设置：生成期间用户换人设/改设置时，过期响应直接丢弃，
    // 避免旧口吻/旧时长的文案挂到新选择上被提交
    const snapPersonaId = personaId.value;
    const snapOptions = scriptAffectingOptions();
    // 画面建议是按发起时的素材来源写的，生成期间用户切了来源也标注这一个
    const snapMaterialMode = opt.material_mode;
    scriptBusy.value = true;
    scriptErr.value = "";
    try {
        const res = await hotspotScript({
            topic: props.topic.title,
            platform: props.topic.platform,
            core_points: research.value.core_points || [],
            summary: research.value.summary || "",
            persona: { id: personaId.value },
            analysis: analysis.value || {},
            options: buildOptions(),
        });
        if (token !== flowToken || personaId.value !== snapPersonaId) return;
        if (scriptAffectingOptions() !== snapOptions) {
            uni.$u.toast("设置已变更，请重新生成文案");
            return;
        }
        // 记下生成时的素材来源：之后切素材来源不作废文案，画面建议仍按生成时的模式标注
        script.value = res ? { ...res, material_mode: snapMaterialMode } : null;
        editedScript.value = String(res?.script || "");
        syncedCta = opt.cta.trim();
        saveFlowSnapshot();
    } catch (error: any) {
        if (token !== flowToken || personaId.value !== snapPersonaId) return;
        scriptErr.value = String(error?.message || error || "文案生成失败");
    } finally {
        if (token === flowToken && personaId.value === snapPersonaId) scriptBusy.value = false;
    }
};

/** 生成视频前的硬性校验（与后端 assertCreateOptions 同口径） */
/** 混剪素材必选：纯人设素材（数字人口播/素材混剪均需用户提供）；「AI+人设素材」需用户提供人设素材 */
const materialRequired = computed(() => opt.material_mode === "ai_persona" || opt.material_mode === "persona");

const videoGate = computed(() => {
    if (opt.video_type === HotspotVideoType.DIGITAL && !opt.avatar_id && !opt.avatar) {
        return "请先选择数字人形象";
    }
    if (materialRequired.value && !submittedMaterials.value.length) {
        return opt.material_mode === "ai_persona" ? "「AI+人设素材」需选择人设素材" : "纯人设素材需选择混剪素材";
    }
    // 兜底：缓存还原等路径带入的超限组合（正常选择在 toggleMaterial 已拦截）
    if (selectedMaterialDuration.value > MATERIAL_DURATION_LIMIT) {
        return `素材总时长不能超过${montageConfig.materialTotalDuration}分钟`;
    }
    return "";
});

const costBadge = computed(() => {
    const cost = props.optionsData.costs?.[opt.video_type];
    if (!cost || !Number(cost.score)) return "";
    return `消耗${Number(cost.score).toFixed(2)}${cost.unit || "算力/秒"}`;
});

const mainBtn = computed(() => {
    // gate=true：必填项未操作，按钮灰显但可点，点击展开高级设置并提示
    if (researchErr.value) return { text: "重试搜索", disabled: false, cost: "" };
    if (!research.value) return { text: "搜索中…", disabled: true, cost: "" };
    if (analyzing.value) return { text: "分析中…", disabled: true, cost: "" };
    if (!personaId.value) return { text: "请先选择人设", disabled: true, cost: "" };
    if (analyzeErr.value) return { text: "重试分析", disabled: false, cost: "" };
    if (script.value) {
        // 重新生成期间禁止用旧文案去生成视频，避免新文案回来前提交
        if (scriptBusy.value) return { text: "口播文案重新生成中…", disabled: true, cost: "" };
        if (videoGate.value) return { text: videoGate.value, disabled: false, gate: true, cost: "" };
        return {
            text: creating.value ? "创建中…" : "开始生成视频",
            disabled: creating.value,
            cost: costBadge.value,
        };
    }
    // 第三步高级设置有必填项未操作时，按钮先提示必填，而不是直接显示第四步的「确认设置，生成文案」
    if (!scriptBusy.value && videoGate.value) {
        return { text: `高级设置：${videoGate.value}`, disabled: false, gate: true, cost: "" };
    }
    return {
        text: scriptBusy.value ? "生成中…" : "确认设置，生成文案",
        disabled: scriptBusy.value,
        cost: "",
    };
});

const handleMainAction = () => {
    if (researchErr.value) {
        runResearch();
        loadInsight();
        return;
    }
    if (analyzeErr.value && personaId.value) {
        pickPersona(personaId.value);
        return;
    }
    if (videoGate.value) {
        showAdv.value = true;
        uni.$u.toast(videoGate.value);
        return;
    }
    if (script.value) {
        startVideo();
        return;
    }
    runScript();
};

const startVideo = async () => {
    if (creating.value || !props.topic || !script.value || !personaId.value) return;
    const finalScript = editedScript.value.trim();
    if (!finalScript) {
        uni.$u.toast("口播文案不能为空");
        return;
    }
    creating.value = true;
    try {
        const task = await addHotspotTask({
            topic: props.topic.title,
            platform: props.topic.platform,
            title: script.value.title || props.topic.title,
            script: finalScript,
            hashtags: script.value.hashtags || [],
            shots: script.value.shots || [],
            persona: { id: personaId.value },
            core_points: research.value?.core_points || [],
            citations: research.value?.citations || [],
            analysis: analysis.value || {},
            options: buildOptions(),
        });
        // 用户在创建期间关掉了面板：仅刷新队列，不再强制跳转详情页
        const stillOpen = props.modelValue;
        emit("created", task || {}, stillOpen);
        if (stillOpen) emit("update:modelValue", false);
    } catch (error: any) {
        uni.$u.toast(error?.message || error || "创建任务失败");
    } finally {
        creating.value = false;
    }
};

// 文案已生成后改结尾引导语：不作废文案，直接同步替换文案末尾的引导语
watch(
    () => opt.cta,
    (value) => {
        if (suppressScriptReset || !script.value || scriptBusy.value) return;
        syncCtaIntoScript(String(value || ""));
        saveFlowSnapshot();
    },
);

// 目的/方向/时长影响文案内容，改动后旧文案作废需重新生成；
// 视频类型/形象/素材/素材来源只决定画面不改口播内容，切换不作废文案：
// 还原已分析热点后随手切一下素材来源就丢掉已有（甚至手改过的）文案、还要再花一次算力重生成
watch(
    () => [opt.goal, opt.direction, opt.duration_sec],
    () => {
        // 缓存/服务端还原是「回放」而非用户改设置，此时不作废文案
        if (suppressScriptReset) return;
        if (script.value && !scriptBusy.value) {
            script.value = null;
            editedScript.value = "";
        }
    },
);

watch(
    () => props.modelValue,
    async (visible) => {
        if (!visible) {
            // 关闭时把最新状态（含高级设置/编辑过的文案）落缓存，下次进入还原到停留步骤
            saveFlowSnapshot();
            return;
        }
        resetFlow();
        // 还原优先级：本地缓存 → 服务端最近任务现场（已分析热点）→ 全新流程
        const cached = readFlowCacheAll()[cacheKey(props.topic)];
        if (cached?.research) {
            restoreFlowFromCache(cached);
            return;
        }
        if (props.topic?.analyzed) {
            const token = flowToken;
            try {
                const last = await getHotspotLastFlow({
                    topic: props.topic.title,
                    platform: props.topic.platform,
                });
                if (token !== flowToken) return;
                if (last?.found && restoreFlowFromServer(last)) return;
            } catch {
                // 还原失败退回全新流程
            }
            if (token !== flowToken) return;
        }
        runResearch();
        loadInsight();
    },
);
</script>

<style lang="scss" scoped>
.flow-card {
    @apply bg-white rounded-[24rpx] p-[24rpx];
    box-shadow: 0 4px 14px rgba(99, 120, 200, 0.08);
}

.step-no {
    @apply w-[36rpx] h-[36rpx] rounded-[8rpx] flex items-center justify-center flex-shrink-0;

    text {
        @apply text-[20rpx] font-bold;
    }
}

.adv-label {
    @apply block text-[24rpx] font-bold text-[#374151] mb-[14rpx];
}

.err-box {
    @apply flex items-start gap-[10rpx] px-[20rpx] py-[16rpx] rounded-[16rpx] bg-[#FEF2F2];
    border: 2rpx solid #fecaca;
}

.cta-gradient {
    background: linear-gradient(90deg, #2563eb 0%, #3b82f6 60%, #4f8cf7 100%);
    box-shadow: 0 10px 22px rgba(37, 99, 235, 0.32);
}

.plain-btn {
    border: none;
    line-height: 1.4;

    &::after {
        border: none;
    }
}
</style>
