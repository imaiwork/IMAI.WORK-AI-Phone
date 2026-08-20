<template>
    <popup
        ref="popupRef"
        :title="currentType ? currentTypeItem?.title : '获客与截流设置'"
        :async="true"
        width="700px"
        :confirm-loading="isLock"
        :show-footer="!!currentType"
        @confirm="lockFn"
        @close="close">
        <div class="page-container">
            <transition name="back-btn">
                <el-button v-if="currentType" text :icon="ArrowLeft" class="back-btn mr-2" @click="backToList">
                    返回
                </el-button>
            </transition>
            <div class="page-screen" :class="currentType ? 'page-screen--exit' : 'page-screen--active'">
                <template v-if="listLoading">
                    <div
                        v-for="i in 5"
                        :key="i"
                        class="bg-white rounded-xl p-4 mb-3 border border-[#F3F4F6] animate-pulse">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-[#F3F4F6]"></div>
                            <div class="flex flex-col gap-2 flex-1">
                                <div class="h-4 w-1/3 bg-[#F3F4F6] rounded-full"></div>
                                <div class="h-3 w-2/3 bg-[#F3F4F6] rounded-full"></div>
                            </div>
                        </div>
                    </div>
                </template>
                <template v-else>
                    <div
                        v-for="item in typeList"
                        :key="item.type"
                        class="flex items-center justify-between p-4 bg-white rounded-xl mb-3 border border-[#F3F4F6] cursor-pointer hover:shadow-md hover:border-primary/30 transition-all duration-200 group"
                        @click="enterSetting(item.type)">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 text-lg"
                                :style="{ background: item.iconBg }">
                                {{ item.icon }}
                            </div>
                            <div class="flex flex-col">
                                <span class="text-sm font-extrabold text-[#111827]">{{ item.title }}</span>
                                <span class="text-xs text-[#9CA3AF] mt-0.5">{{ item.desc }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            <span class="text-xs text-[#9CA3AF] group-hover:text-primary transition-colors"
                                >去配置</span
                            >
                            <el-icon size="14" class="text-[#9CA3AF] group-hover:text-primary transition-colors">
                                <ArrowRight />
                            </el-icon>
                        </div>
                    </div>
                </template>
            </div>

            <div class="page-screen" :class="currentType ? 'page-screen--active' : 'page-screen--enter'">
                <template v-if="settingLoading">
                    <div
                        v-for="i in 3"
                        :key="i"
                        class="bg-white rounded-xl p-4 mb-3 border border-[#F3F4F6] animate-pulse">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-xl bg-[#F3F4F6]"></div>
                            <div class="flex flex-col gap-2 flex-1">
                                <div class="h-4 w-1/3 bg-[#F3F4F6] rounded-full"></div>
                                <div class="h-3 w-2/3 bg-[#F3F4F6] rounded-full"></div>
                            </div>
                        </div>
                    </div>
                </template>

                <template v-else>
                    <template v-if="currentType === TrafficConfigType.SPH">
                        <config-card-inner
                            title="视频号获客"
                            desc="监控视频号账号，出现以下词汇立即寻找线索"
                            icon="📡"
                            icon-bg="#FFF0F0">
                            <tag-list-inner
                                :items="configData.acquisitionWords"
                                add-text="添加"
                                @add="handleAdd('acquisitionWords')"
                                @edit="handleEdit('acquisitionWords', $event)"
                                @remove="removeTag('acquisitionWords', $event)" />
                            <div class="mt-4 pt-4 border-t border-[#F3F4F6]">
                                <div class="flex items-center gap-2 mb-3">
                                    <el-icon color="#9CA3AF"><Setting /></el-icon>
                                    <span class="text-sm font-bold text-[#212121]">执行策略</span>
                                </div>
                                <div class="flex items-center justify-between mb-4">
                                    <span class="text-sm font-bold text-[#212121]">每个线索词获客上限</span>
                                    <div class="flex items-center gap-2">
                                        <el-input-number
                                            v-model="configData.acquisitionLimit"
                                            :min="1"
                                            controls-position="right"
                                            style="width: 120px" />
                                        <span class="text-sm text-[#888888]">个</span>
                                    </div>
                                </div>
                                <div class="flex gap-2" v-if="false">
                                    <div
                                        v-for="option in STRATEGY_LIST"
                                        :key="option.value"
                                        class="flex-1 h-9 flex items-center justify-center rounded-lg cursor-pointer border border-solid transition-all text-xs font-semibold"
                                        :class="
                                            configData.acquisitionStrategy == option.value
                                                ? 'border-primary bg-[#EBF2FF] text-primary'
                                                : 'border-[#E5E7EB] bg-[#F9FAFB] text-[#9CA3AF]'
                                        "
                                        @click="configData.acquisitionStrategy = option.value">
                                        {{ option.label }}
                                    </div>
                                </div>
                            </div>
                        </config-card-inner>
                    </template>

                    <template v-if="currentType === TrafficConfigType.Video">
                        <config-card-inner
                            title="视频截流设置"
                            desc="搜索同行视频，并监控其评论区寻找潜在客户"
                            icon="🎬"
                            icon-bg="#FFF5F0">
                            <div class="mb-4">
                                <div class="flex items-center gap-2 mb-3">
                                    <div
                                        class="w-5 h-5 rounded-full bg-[#FF8C00]/10 flex items-center justify-center flex-shrink-0">
                                        <span class="text-xs font-extrabold text-[#FF8C00]">1</span>
                                    </div>
                                    <span class="text-sm font-bold text-[#212121]">视频搜索词</span>
                                    <span class="text-xs text-[#999999]">用于在社媒平台搜索相关的同行视频</span>
                                </div>
                                <tag-list-inner
                                    :items="configData.interceptionSearchWords"
                                    add-text="添加"
                                    @add="handleAdd('interceptionSearchWords')"
                                    @edit="handleEdit('interceptionSearchWords', $event)"
                                    @remove="removeTag('interceptionSearchWords', $event)" />
                            </div>
                            <div class="pt-4 border-t border-[#F3F4F6] mb-4">
                                <div class="flex items-center gap-2 mb-3">
                                    <div
                                        class="w-5 h-5 rounded-full bg-[#FF8C00]/10 flex items-center justify-center flex-shrink-0">
                                        <span class="text-xs font-extrabold text-[#FF8C00]">2</span>
                                    </div>
                                    <span class="text-sm font-bold text-[#212121]">评论匹配词</span>
                                    <span class="text-xs text-[#999999]"
                                        >在上述视频的评论区中，筛选出包含以下意向词的客户</span
                                    >
                                </div>
                                <tag-list-inner
                                    :items="configData.interceptionMatchWords"
                                    add-text="添加"
                                    @add="handleAdd('interceptionMatchWords')"
                                    @edit="handleEdit('interceptionMatchWords', $event)"
                                    @remove="removeTag('interceptionMatchWords', $event)" />
                            </div>
                            <div class="pt-4 border-t border-[#F3F4F6]">
                                <div class="flex items-center gap-2 mb-3">
                                    <el-icon color="#9CA3AF"><Setting /></el-icon>
                                    <span class="text-sm font-bold text-[#212121]">执行策略</span>
                                </div>
                                <div class="flex items-center justify-between mb-4">
                                    <span class="text-sm font-bold text-[#212121]">每个匹配词截流上限</span>
                                    <div class="flex items-center gap-2">
                                        <el-input-number
                                            v-model="configData.interceptionLimit"
                                            :min="1"
                                            controls-position="right"
                                            style="width: 120px" />
                                        <span class="text-sm text-[#888888]">个</span>
                                    </div>
                                </div>
                                <div class="flex gap-2" v-if="false">
                                    <div
                                        v-for="option in STRATEGY_LIST"
                                        :key="option.value"
                                        class="flex-1 h-9 flex items-center justify-center rounded-lg cursor-pointer border border-solid transition-all text-xs font-semibold"
                                        :class="
                                            configData.interceptionStrategy == option.value
                                                ? 'border-primary bg-[#EBF2FF] text-primary'
                                                : 'border-[#E5E7EB] bg-[#F9FAFB] text-[#9CA3AF]'
                                        "
                                        @click="configData.interceptionStrategy = option.value">
                                        {{ option.label }}
                                    </div>
                                </div>
                            </div>
                        </config-card-inner>
                    </template>

                    <template v-if="currentType === TrafficConfigType.GroupPurchase">
                        <config-card-inner
                            title="团购截流配置"
                            desc="搜索或指定团购，监控评论区寻找潜在客户"
                            icon="🛒"
                            icon-bg="#E6F0FF">
                            <div class="flex bg-[#F0F2F5] p-1 rounded-xl mb-4" v-if="false">
                                <div
                                    v-for="tab in GROUPON_TAB_LIST"
                                    :key="tab.value"
                                    class="flex-1 h-9 flex items-center justify-center rounded-lg cursor-pointer transition-all"
                                    :class="configData.grouponTab == tab.value ? 'bg-white shadow-sm' : ''"
                                    @click="configData.grouponTab = tab.value">
                                    <span
                                        class="text-sm font-bold"
                                        :class="
                                            configData.grouponTab == tab.value ? 'text-[#0D1117]' : 'text-[#9CA3AF]'
                                        ">
                                        {{ tab.label }}
                                    </span>
                                </div>
                            </div>
                            <template v-if="configData.grouponTab == GrouponTab.Search">
                                <div class="section-block mb-4">
                                    <div class="section-title">搜索与定位设置</div>
                                    <div class="mb-3">
                                        <span class="field-label">输入团购类型</span>
                                        <el-input
                                            v-model="configData.grouponTypeKeyword"
                                            placeholder="如：双人套餐、火锅、美甲..."
                                            clearable />
                                    </div>
                                    <div v-if="false">
                                        <span class="field-label">团购距离范围</span>
                                        <div class="flex flex-wrap gap-2 mb-2">
                                            <div
                                                v-for="item in DISTANCE_LIST"
                                                :key="item.value"
                                                class="h-8 px-4 flex items-center justify-center rounded-lg cursor-pointer transition-all text-xs font-bold"
                                                :class="
                                                    !isGrouponCustomDistance && configData.grouponDistance == item.value
                                                        ? 'bg-[#EBF2FF] text-primary border border-solid border-[#BFDBFE]'
                                                        : 'bg-[#F0F2F5] text-[#9CA3AF]'
                                                "
                                                @click="handleGrouponSelectDistance(item.value)">
                                                {{ item.label }}
                                            </div>
                                            <div
                                                class="h-8 px-4 flex items-center justify-center rounded-lg cursor-pointer transition-all text-xs font-bold border border-solid"
                                                :class="
                                                    isGrouponCustomDistance
                                                        ? 'bg-[#EBF2FF] border-primary text-primary'
                                                        : 'bg-[#F0F2F5] border-[transparent] text-[#9CA3AF]'
                                                "
                                                @click="isGrouponCustomDistance = true">
                                                自定义
                                            </div>
                                        </div>
                                        <div v-if="isGrouponCustomDistance" class="flex items-center gap-2">
                                            <el-input
                                                v-model="grouponCustomDistanceInput"
                                                placeholder="请输入距离数值"
                                                type="number"
                                                style="width: 160px"
                                                @blur="handleGrouponCustomDistanceBlur" />
                                            <span class="text-sm text-[#888888]">公里</span>
                                        </div>
                                    </div>
                                </div>
                            </template>
                            <div class="section-block mb-4">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="section-title mb-0">任务目标与评论筛选</div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-[#9CA3AF]">执行</span>
                                        <el-input-number
                                            v-model="configData.commentNumber"
                                            :min="1"
                                            controls-position="right"
                                            style="width: 100px" />
                                        <span class="text-xs text-[#9CA3AF]">人</span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <span class="field-label">评论必须包含以下关键词</span>
                                    <div class="flex flex-wrap gap-2 mb-2">
                                        <div
                                            v-for="(kw, idx) in configData.grouponCommentKeywords"
                                            :key="idx"
                                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#EBF2FF] border border-solid border-[#BFDBFE] cursor-pointer"
                                            @click="handleEditGrouponKeyword(idx)">
                                            <span class="text-xs font-semibold text-primary">{{ kw }}</span>
                                            <div
                                                class="w-4 h-4 rounded-full bg-primary/10 flex items-center justify-center"
                                                @click.stop="configData.grouponCommentKeywords.splice(idx, 1)">
                                                <el-icon size="10" color="#0065fb"><Close /></el-icon>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex gap-2">
                                        <el-input
                                            v-model="grouponCommentKeywordInput"
                                            placeholder="如：怎么买、划算吗..."
                                            @keyup.enter="handleAddGrouponKeyword" />
                                        <el-button type="primary" @click="handleAddGrouponKeyword">添加</el-button>
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <div
                                        class="flex-1 bg-[#F7F9FC] rounded-xl p-3 border border-[#E5E9F0]"
                                        v-if="false">
                                        <span class="field-label">发布少于（天）</span>
                                        <el-input-number
                                            v-model="configData.grouponPublishDay"
                                            :min="0"
                                            controls-position="right"
                                            class="w-full" />
                                    </div>
                                    <div class="flex-1 bg-[#F7F9FC] rounded-xl p-3 border border-[#E5E9F0]">
                                        <span class="field-label">从第几个评论开始</span>
                                        <el-input-number
                                            v-model="configData.grouponCommentNum"
                                            :min="1"
                                            controls-position="right"
                                            class="w-full" />
                                    </div>
                                </div>
                            </div>
                            <div class="section-block mb-4">
                                <div class="section-title">对评论用户执行动作</div>
                                <div class="flex gap-3 mb-3">
                                    <div
                                        v-for="action in GROUPON_FREE_ACTION_LIST"
                                        :key="action.value"
                                        class="flex-1 relative flex flex-col items-center justify-center py-3 rounded-xl border-2 border-solid cursor-pointer transition-all"
                                        :class="
                                            configData.grouponActions.includes(action.value)
                                                ? 'border-primary bg-[#EBF2FF]'
                                                : 'border-[#F0F2F5] bg-[#F7F9FC]'
                                        "
                                        @click="toggleGrouponFreeAction(action.value)">
                                        <span class="text-lg mb-1">{{ action.icon }}</span>
                                        <span
                                            class="text-xs font-semibold"
                                            :class="
                                                configData.grouponActions.includes(action.value)
                                                    ? 'text-primary'
                                                    : 'text-[#9CA3AF]'
                                            ">
                                            {{ action.label }}
                                        </span>
                                        <div
                                            v-if="configData.grouponActions.includes(action.value)"
                                            class="absolute top-1 right-1 w-4 h-4 rounded-full bg-primary flex items-center justify-center">
                                            <el-icon size="10" color="#fff"><Check /></el-icon>
                                        </div>
                                    </div>

                                    <div
                                        class="flex-1 relative rounded-xl border-2 border-solid cursor-pointer transition-all overflow-hidden"
                                        :class="grouponHasMutexSelected ? 'border-primary' : 'border-[#F0F2F5]'">
                                        <div
                                            class="absolute top-0 left-0 right-0 flex justify-center"
                                            style="z-index: 1">
                                            <div
                                                class="px-2 h-5 flex items-center rounded-b-lg transition-all"
                                                :class="grouponHasMutexSelected ? 'bg-primary' : 'bg-[#E5E7EB]'">
                                                <span
                                                    class="text-[10px] font-semibold"
                                                    :class="grouponHasMutexSelected ? 'text-white' : 'text-[#9CA3AF]'">
                                                    二选一
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex h-full pt-5">
                                            <div
                                                v-for="(action, idx) in GROUPON_MUTEX_ACTION_LIST"
                                                :key="action.value"
                                                class="flex-1 flex flex-col items-center justify-center py-3 transition-all relative"
                                                :class="[
                                                    configData.grouponActions.includes(action.value)
                                                        ? 'bg-[#EBF2FF]'
                                                        : 'bg-[#F7F9FC]',
                                                    idx === 0 ? 'border-r border-solid border-[#E5E7EB]' : '',
                                                ]"
                                                @click="toggleGrouponMutexAction(action.value)">
                                                <span class="text-lg mb-1">{{ action.icon }}</span>
                                                <span
                                                    class="text-xs font-semibold"
                                                    :class="
                                                        configData.grouponActions.includes(action.value)
                                                            ? 'text-primary'
                                                            : 'text-[#9CA3AF]'
                                                    ">
                                                    {{ action.label }}
                                                </span>
                                                <div
                                                    v-if="configData.grouponActions.includes(action.value)"
                                                    class="absolute top-1 right-1 w-4 h-4 rounded-full bg-primary flex items-center justify-center">
                                                    <el-icon size="10" color="#fff"><Check /></el-icon>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-[#F9FAFB] rounded-xl p-3 border border-[#E5E9F0] mb-3" v-if="false">
                                    <span class="text-xs font-semibold text-[#0D1117] block mb-2">点赞方式</span>
                                    <div class="flex bg-[#F0F2F5] rounded-lg p-1">
                                        <div
                                            v-for="item in LIKE_TYPE_LIST"
                                            :key="item.value"
                                            class="flex-1 h-8 flex items-center justify-center rounded-md cursor-pointer transition-all"
                                            :class="
                                                configData.grouponLikeType == item.value ? 'bg-white shadow-sm' : ''
                                            "
                                            @click="configData.grouponLikeType = item.value">
                                            <span
                                                class="text-xs font-semibold"
                                                :class="
                                                    configData.grouponLikeType == item.value
                                                        ? 'text-[#0D1117]'
                                                        : 'text-[#9CA3AF]'
                                                ">
                                                {{ item.label }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <div class="flex-1 bg-[#F7F9FC] rounded-xl p-3 border border-[#E5E9F0]">
                                        <span class="field-label">观看视频（秒）</span>
                                        <el-input-number
                                            v-model="configData.grouponWatchSeconds"
                                            :min="0"
                                            controls-position="right"
                                            class="w-full" />
                                    </div>
                                    <div class="flex-1 bg-[#F7F9FC] rounded-xl p-3 border border-[#E5E9F0]">
                                        <span class="field-label">触达间隔（秒）</span>
                                        <el-input-number
                                            v-model="configData.grouponReachInterval"
                                            :min="0"
                                            controls-position="right"
                                            class="w-full" />
                                    </div>
                                </div>
                            </div>
                            <div class="section-block mb-4">
                                <div class="section-title">目标画像与地域</div>
                                <div class="mb-3">
                                    <span class="field-label">性别要求</span>
                                    <div class="flex bg-[#F0F2F5] rounded-lg p-1">
                                        <div
                                            v-for="item in GENDER_LIST"
                                            :key="item.value"
                                            class="flex-1 h-8 flex items-center justify-center rounded-md cursor-pointer transition-all"
                                            :class="configData.grouponGender == item.value ? 'bg-white shadow-sm' : ''"
                                            @click="configData.grouponGender = item.value">
                                            <span
                                                class="text-xs font-semibold"
                                                :class="
                                                    configData.grouponGender == item.value
                                                        ? 'text-[#0D1117]'
                                                        : 'text-[#9CA3AF]'
                                                ">
                                                {{ item.label }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <span class="field-label">年龄范围</span>
                                    <div class="flex items-center gap-2">
                                        <el-input-number
                                            v-model="configData.grouponAgeMin"
                                            :min="0"
                                            :max="99"
                                            controls-position="right"
                                            style="width: 100px" />
                                        <span class="text-[#9CA3AF] text-xs">-</span>
                                        <el-input-number
                                            v-model="configData.grouponAgeMax"
                                            :min="0"
                                            :max="99"
                                            controls-position="right"
                                            style="width: 100px" />
                                    </div>
                                </div>
                            </div>
                            <div class="section-block">
                                <div class="section-title">昵称过滤词</div>
                                <div class="flex flex-wrap gap-2 mb-2">
                                    <div
                                        v-for="(kw, idx) in configData.grouponNicknameFilter"
                                        :key="idx"
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#F9FAFB] border border-solid border-[#E5E7EB] cursor-pointer"
                                        @click="handleEditGrouponNickname(idx)">
                                        <span class="text-xs text-[#374151]">{{ kw }}</span>
                                        <div
                                            class="w-4 h-4 rounded-full bg-[#D1D5DB] flex items-center justify-center"
                                            @click.stop="configData.grouponNicknameFilter.splice(idx, 1)">
                                            <el-icon size="10" color="#fff"><Close /></el-icon>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <el-input
                                        v-model="grouponNicknameInput"
                                        placeholder="输入昵称过滤词..."
                                        @keyup.enter="handleAddGrouponNickname" />
                                    <el-button type="primary" @click="handleAddGrouponNickname">添加</el-button>
                                </div>
                            </div>
                        </config-card-inner>
                    </template>

                    <template v-if="currentType === TrafficConfigType.City">
                        <config-card-inner
                            title="同城视频评论截流"
                            desc="挖掘同城视频评论，精准截流引客"
                            icon="🏙️"
                            icon-bg="#E6F0FF">
                            <div class="section-block mb-4">
                                <div class="section-title">互动与触达动作</div>
                                <div class="flex gap-3 mb-3">
                                    <div
                                        v-for="action in CITY_FREE_ACTION_LIST"
                                        :key="action.value"
                                        class="flex-1 relative flex flex-col items-center justify-center py-3 rounded-xl border-2 border-solid cursor-pointer transition-all"
                                        :class="
                                            configData.cityActions.includes(action.value)
                                                ? 'border-primary bg-[#EBF2FF]'
                                                : 'border-[#F0F2F5] bg-[#F7F9FC]'
                                        "
                                        @click="toggleCityFreeAction(action.value)">
                                        <span class="text-lg mb-1">{{ action.icon }}</span>
                                        <span
                                            class="text-xs font-semibold"
                                            :class="
                                                configData.cityActions.includes(action.value)
                                                    ? 'text-primary'
                                                    : 'text-[#9CA3AF]'
                                            ">
                                            {{ action.label }}
                                        </span>
                                        <div
                                            v-if="configData.cityActions.includes(action.value)"
                                            class="absolute top-1 right-1 w-4 h-4 rounded-full bg-primary flex items-center justify-center">
                                            <el-icon size="10" color="#fff"><Check /></el-icon>
                                        </div>
                                    </div>

                                    <div
                                        class="flex-1 relative rounded-xl border-2 border-solid cursor-pointer transition-all overflow-hidden"
                                        :class="cityHasMutexSelected ? 'border-primary' : 'border-[#F0F2F5]'">
                                        <div
                                            class="absolute top-0 left-0 right-0 flex justify-center"
                                            style="z-index: 1">
                                            <div
                                                class="px-2 h-5 flex items-center rounded-b-lg transition-all"
                                                :class="cityHasMutexSelected ? 'bg-primary' : 'bg-[#E5E7EB]'">
                                                <span
                                                    class="text-[10px] font-semibold"
                                                    :class="cityHasMutexSelected ? 'text-white' : 'text-[#9CA3AF]'">
                                                    二选一
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex h-full pt-5">
                                            <div
                                                v-for="(action, idx) in GROUPON_MUTEX_ACTION_LIST"
                                                :key="action.value"
                                                class="flex-1 flex flex-col items-center justify-center py-3 transition-all relative"
                                                :class="[
                                                    configData.cityActions.includes(action.value)
                                                        ? 'bg-[#EBF2FF]'
                                                        : 'bg-[#F7F9FC]',
                                                    idx === 0 ? 'border-r border-solid border-[#E5E7EB]' : '',
                                                ]"
                                                @click="toggleCityMutexAction(action.value)">
                                                <span class="text-lg mb-1">{{ action.icon }}</span>
                                                <span
                                                    class="text-xs font-semibold"
                                                    :class="
                                                        configData.cityActions.includes(action.value)
                                                            ? 'text-primary'
                                                            : 'text-[#9CA3AF]'
                                                    ">
                                                    {{ action.label }}
                                                </span>
                                                <div
                                                    v-if="configData.cityActions.includes(action.value)"
                                                    class="absolute top-1 right-1 w-4 h-4 rounded-full bg-primary flex items-center justify-center">
                                                    <el-icon size="10" color="#fff"><Check /></el-icon>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <div class="flex-1 bg-[#F7F9FC] rounded-xl p-3 border border-[#E5E9F0]">
                                        <span class="field-label">观看视频（秒）</span>
                                        <el-input-number
                                            v-model="configData.cityWatchSeconds"
                                            :min="0"
                                            controls-position="right"
                                            class="w-full" />
                                    </div>
                                    <div class="flex-1 bg-[#F7F9FC] rounded-xl p-3 border border-[#E5E9F0]">
                                        <span class="field-label">触达间隔（秒）</span>
                                        <el-input-number
                                            v-model="configData.cityReachInterval"
                                            :min="0"
                                            controls-position="right"
                                            class="w-full" />
                                    </div>
                                </div>
                            </div>
                            <div class="section-block mb-4">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="section-title mb-0">评论者画像</div>
                                    <span
                                        class="text-xs font-bold text-primary bg-[#EBF2FF] px-3 py-1 rounded-lg border border-[#BFDBFE]">
                                        {{ configData.cityDistance == 0 ? "全城" : configData.cityDistance + "公里内" }}
                                    </span>
                                </div>
                                <div class="mb-3">
                                    <span class="field-label">附近距离范围</span>
                                    <div class="flex flex-wrap gap-2 mb-2">
                                        <div
                                            v-for="item in DISTANCE_LIST"
                                            :key="item.value"
                                            class="h-8 px-4 flex items-center justify-center rounded-lg cursor-pointer transition-all text-xs font-bold"
                                            :class="
                                                !isCityCustomDistance && configData.cityDistance == item.value
                                                    ? 'bg-[#EBF2FF] text-primary border border-solid border-[#BFDBFE]'
                                                    : 'bg-[#F0F2F5] text-[#9CA3AF]'
                                            "
                                            @click="handleCitySelectDistance(item.value)">
                                            {{ item.label }}
                                        </div>
                                        <div
                                            class="h-8 px-4 flex items-center justify-center rounded-lg cursor-pointer transition-all text-xs font-bold border border-solid"
                                            :class="
                                                isCityCustomDistance
                                                    ? 'bg-[#EBF2FF] border-primary text-primary'
                                                    : 'bg-[#F0F2F5] border-[transparent] text-[#9CA3AF]'
                                            "
                                            @click="isCityCustomDistance = true">
                                            自定义
                                        </div>
                                    </div>
                                    <div v-if="isCityCustomDistance" class="flex items-center gap-2">
                                        <el-input
                                            v-model="cityCustomDistanceInput"
                                            placeholder="请输入距离数值"
                                            type="number"
                                            style="width: 160px"
                                            @blur="handleCityCustomDistanceBlur" />
                                        <span class="text-sm text-[#888888]">公里</span>
                                    </div>
                                </div>
                                <div class="flex gap-4">
                                    <div class="flex-1">
                                        <span class="field-label">性别要求</span>
                                        <div class="flex bg-[#F0F2F5] rounded-lg p-1">
                                            <div
                                                v-for="item in GENDER_LIST"
                                                :key="item.value"
                                                class="flex-1 h-8 flex items-center justify-center rounded-md cursor-pointer transition-all"
                                                :class="
                                                    configData.cityGenderFilter == item.value
                                                        ? 'bg-white shadow-sm'
                                                        : ''
                                                "
                                                @click="configData.cityGenderFilter = item.value">
                                                <span
                                                    class="text-xs font-semibold"
                                                    :class="
                                                        configData.cityGenderFilter == item.value
                                                            ? 'text-[#0D1117]'
                                                            : 'text-[#9CA3AF]'
                                                    ">
                                                    {{ item.label }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex-1">
                                        <span class="field-label">年龄范围</span>
                                        <div class="flex items-center gap-2">
                                            <el-input-number
                                                v-model="configData.cityAgeMin"
                                                :min="0"
                                                :max="99"
                                                controls-position="right"
                                                style="width: 90px" />
                                            <span class="text-[#9CA3AF] text-xs">-</span>
                                            <el-input-number
                                                v-model="configData.cityAgeMax"
                                                :min="0"
                                                :max="99"
                                                controls-position="right"
                                                style="width: 90px" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="section-block mb-4">
                                <div class="section-title">作品与账号过滤</div>
                                <div class="flex gap-3 mb-3">
                                    <div class="flex-1 bg-[#F7F9FC] rounded-xl p-3 border border-[#E5E9F0]">
                                        <span class="field-label">视频作品满足（赞）以上</span>
                                        <el-input-number
                                            v-model="configData.cityVideoMatchNum"
                                            :min="0"
                                            controls-position="right"
                                            class="w-full" />
                                    </div>
                                    <div class="flex-1 bg-[#F7F9FC] rounded-xl p-3 border border-[#E5E9F0]">
                                        <span class="field-label">视频评论数不大于</span>
                                        <el-input-number
                                            v-model="configData.cityVideoCommentNum"
                                            :min="0"
                                            controls-position="right"
                                            class="w-full" />
                                    </div>
                                </div>
                                <div class="flex gap-3">
                                    <div class="flex-1 bg-[#F7F9FC] rounded-xl p-3 border border-[#E5E9F0]">
                                        <span class="field-label">目标评论粉丝数量</span>
                                        <div class="flex items-center gap-2">
                                            <el-input-number
                                                v-model="configData.cityCommentFansMin"
                                                :min="0"
                                                controls-position="right"
                                                style="width: 90px" />
                                            <span class="text-[#9CA3AF] text-xs">-</span>
                                            <el-input-number
                                                v-model="configData.cityCommentFansMax"
                                                :min="0"
                                                controls-position="right"
                                                style="width: 90px" />
                                        </div>
                                    </div>
                                    <div class="flex-1 bg-[#F7F9FC] rounded-xl p-3 border border-[#E5E9F0]">
                                        <span class="field-label">目标评论关注数量</span>
                                        <div class="flex items-center gap-2">
                                            <el-input-number
                                                v-model="configData.cityFollowMin"
                                                :min="0"
                                                controls-position="right"
                                                style="width: 90px" />
                                            <span class="text-[#9CA3AF] text-xs">-</span>
                                            <el-input-number
                                                v-model="configData.cityFollowMax"
                                                :min="0"
                                                controls-position="right"
                                                style="width: 90px" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="section-block mb-4">
                                <div class="section-title">昵称过滤词</div>
                                <div class="flex flex-wrap gap-2 mb-2">
                                    <div
                                        v-for="(kw, idx) in configData.cityNicknameFilter"
                                        :key="idx"
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#F9FAFB] border border-solid border-[#E5E7EB] cursor-pointer"
                                        @click="handleEditCityNickname(idx)">
                                        <span class="text-xs text-[#374151]">{{ kw }}</span>
                                        <div
                                            class="w-4 h-4 rounded-full bg-[#D1D5DB] flex items-center justify-center"
                                            @click.stop="configData.cityNicknameFilter.splice(idx, 1)">
                                            <el-icon size="10" color="#fff"><Close /></el-icon>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <el-input
                                        v-model="cityNicknameInput"
                                        placeholder="输入昵称过滤词..."
                                        @keyup.enter="handleAddCityNickname" />
                                    <el-button type="primary" @click="handleAddCityNickname">添加</el-button>
                                </div>
                            </div>
                        </config-card-inner>
                    </template>

                    <template v-if="currentType === TrafficConfigType.Guard">
                        <config-card-inner title="触达时间限制" desc="" icon="🕐" icon-bg="#F3F0FF">
                            <div class="mb-5">
                                <div class="text-sm font-bold mb-2 text-[#212121]">内容发布日期</div>
                                <div class="grid grid-cols-4 gap-2">
                                    <div
                                        v-for="option in TIME_LIST"
                                        :key="'content-' + option.value"
                                        class="h-9 rounded-lg flex items-center justify-center cursor-pointer transition-all select-none text-xs font-medium"
                                        :style="
                                            configData.contentPublishTime == option.value
                                                ? 'background:#0065fb;color:#ffffff'
                                                : 'background:#F5F5F5;color:#888888'
                                        "
                                        @click="configData.contentPublishTime = option.value">
                                        {{ option.label }}
                                    </div>
                                </div>
                            </div>
                            <div>
                                <div class="text-sm font-bold mb-2 text-[#212121]">评论发布日期</div>
                                <div class="grid grid-cols-4 gap-2">
                                    <div
                                        v-for="option in TIME_LIST"
                                        :key="'comment-' + option.value"
                                        class="h-9 rounded-lg flex items-center justify-center cursor-pointer transition-all select-none text-xs font-medium"
                                        :style="
                                            configData.commentPublishTime == option.value
                                                ? 'background:#0065fb;color:#ffffff'
                                                : 'background:#F5F5F5;color:#888888'
                                        "
                                        @click="configData.commentPublishTime = option.value">
                                        {{ option.label }}
                                    </div>
                                </div>
                            </div>
                        </config-card-inner>
                        <config-card-inner title="防封控与频率限制" desc="" icon="⚙️" icon-bg="#E6F0FF">
                            <div class="rounded-lg p-3 mb-5 text-xs text-primary leading-relaxed bg-[#E6F0FF]/60">
                                已开启"拟人随机停顿"。每次互动后，系统将随机停留
                                30秒~2分钟，模拟真人浏览行为，降低风控风险。
                            </div>
                            <div class="mb-5">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-bold text-[#212121]">截流主动私信每天最大互动人数</span>
                                    <span class="text-sm font-extrabold text-primary"
                                        >{{ configData.messageNumber }}人</span
                                    >
                                </div>
                                <el-slider
                                    v-model="configData.messageNumber"
                                    :min="0"
                                    :max="30"
                                    :show-tooltip="false"
                                    class="mb-1" />
                                <div class="flex justify-between text-xs text-[#b4b4b4]">
                                    <span>保守 (防封)</span><span>激进 (易封)</span>
                                </div>
                            </div>
                            <div class="mb-5">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-bold text-[#212121]">同城触达评论每天最大互动人数</span>
                                    <span class="text-sm font-extrabold text-primary"
                                        >{{ configData.cityCommentNumber }}人</span
                                    >
                                </div>
                                <el-slider
                                    v-model="configData.cityCommentNumber"
                                    :min="0"
                                    :max="30"
                                    :show-tooltip="false"
                                    class="mb-1" />
                                <div class="flex justify-between text-xs text-[#b4b4b4]">
                                    <span>保守 (防封)</span><span>激进 (易封)</span>
                                </div>
                            </div>
                            <div class="mb-5">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-bold text-[#212121]">每日视频截流人数上限</span>
                                    <span class="text-sm font-extrabold text-primary"
                                        >{{ configData.videoMessageNumber }}人</span
                                    >
                                </div>
                                <el-slider
                                    v-model="configData.videoMessageNumber"
                                    :min="0"
                                    :max="30"
                                    :show-tooltip="false"
                                    class="mb-1" />
                                <div class="flex justify-between text-xs text-[#b4b4b4]">
                                    <span>保守 (防封)</span><span>激进 (易封)</span>
                                </div>
                            </div>
                            <div class="mb-5">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-bold text-[#212121]">每日同城截流人数上限</span>
                                    <span class="text-sm font-extrabold text-primary"
                                        >{{ configData.cityMessageNumber }}人</span
                                    >
                                </div>
                                <el-slider
                                    v-model="configData.cityMessageNumber"
                                    :min="0"
                                    :max="30"
                                    :show-tooltip="false"
                                    class="mb-1" />
                                <div class="flex justify-between text-xs text-[#b4b4b4]">
                                    <span>保守 (防封)</span><span>激进 (易封)</span>
                                </div>
                            </div>
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-bold text-[#212121]">每日团购截流人数上限</span>
                                    <span class="text-sm font-extrabold text-primary"
                                        >{{ configData.grouponMessageNumber }}人</span
                                    >
                                </div>
                                <el-slider
                                    v-model="configData.grouponMessageNumber"
                                    :min="1"
                                    :max="30"
                                    :show-tooltip="false"
                                    class="mb-1" />
                                <div class="flex justify-between text-xs text-[#b4b4b4]">
                                    <span>保守 (防封)</span><span>激进 (易封)</span>
                                </div>
                            </div>
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-bold text-[#212121]">私信每接管个用户回复数</span>
                                    <span class="text-sm font-extrabold text-primary">{{
                                        configData.replyNumber == 1 ? "1条" : "无限制"
                                    }}</span>
                                </div>
                                <el-slider
                                    v-model="configData.replyNumber"
                                    :min="1"
                                    :max="2"
                                    :show-tooltip="false"
                                    class="mb-1" />
                                <div class="flex justify-between text-xs text-[#b4b4b4]">
                                    <span>1条</span><span>无限制</span>
                                </div>
                            </div>
                        </config-card-inner>
                    </template>
                </template>
            </div>
        </div>
    </popup>
</template>

<script setup lang="ts">
import { defineComponent, h, ref, reactive, computed, watch, shallowRef } from "vue";
import { User, Plus, Close, ArrowUp, ArrowDown, ArrowRight, ArrowLeft, Check, Setting } from "@element-plus/icons-vue";
import { ElMessage, ElMessageBox } from "element-plus";
import { getTrafficConfig, updateTrafficConfig } from "@/api/ai_application/device/person";
import Popup from "@/components/popup/index.vue";
import { useLockFn } from "@/hooks/useLockFn";

enum TrafficConfigType {
    SPH = 1,
    Video = 2,
    GroupPurchase = 3,
    City = 4,
    Guard = 5,
}
enum GrouponTab {
    Favorite = 1,
    Search = 2,
}
enum GrouponAction {
    Like = 1,
    Follow = 2,
    Comment = 3,
    Dm = 4,
}

const TIME_LIST = [
    { label: "当天", value: 1 },
    { label: "2天内", value: 2 },
    { label: "3天内", value: 3 },
    { label: "4天内", value: 4 },
    { label: "5天内", value: 5 },
    { label: "6天内", value: 6 },
    { label: "7天内", value: 7 },
    { label: "不限制", value: -1 },
];
const STRATEGY_LIST = [
    { label: "AI自动补充", value: 1 },
    { label: "循环使用", value: 2 },
    { label: "停止使用", value: 3 },
];
const GROUPON_TAB_LIST = [
    { label: "收藏团购", value: GrouponTab.Favorite },
    { label: "搜索团购", value: GrouponTab.Search },
];
const GROUPON_ACTION_LIST = [
    { label: "点赞", value: GrouponAction.Like, icon: "👍" },
    { label: "关注", value: GrouponAction.Follow, icon: "➕" },
    { label: "评论", value: GrouponAction.Comment, icon: "💬" },
    { label: "私信", value: GrouponAction.Dm, icon: "✉️" },
];
const COMMENT_USER_ACTION_LIST = [GrouponAction.Comment, GrouponAction.Dm];
const isCommentUserAction = (value: GrouponAction | number): value is GrouponAction.Comment | GrouponAction.Dm =>
    COMMENT_USER_ACTION_LIST.includes(Number(value) as GrouponAction);
const CITY_FREE_ACTION_LIST: typeof GROUPON_ACTION_LIST = [];
const GROUPON_FREE_ACTION_LIST: typeof GROUPON_ACTION_LIST = [];
const GROUPON_MUTEX_ACTION_LIST = GROUPON_ACTION_LIST.filter((a) => isCommentUserAction(a.value));
const filterCommentUserActions = (value: unknown): GrouponAction[] =>
    Array.isArray(value) ? value.map((item) => Number(item)).filter(isCommentUserAction) : [];
const normalizeCommentUserActions = (value: unknown): GrouponAction[] => {
    const actions = filterCommentUserActions(value);
    return actions.length ? actions : [GrouponAction.Comment];
};
const LIKE_TYPE_LIST = [
    { label: "点赞视频", value: 1 },
    { label: "点赞评论", value: 2 },
];
const GENDER_LIST = [
    { label: "不限", value: 0 },
    { label: "男", value: 1 },
    { label: "女", value: 2 },
];
const DISTANCE_LIST = [
    { label: "全城", value: 0 },
    { label: "1公里", value: 1 },
    { label: "3公里", value: 3 },
    { label: "5公里", value: 5 },
    { label: "10公里", value: 10 },
];
const typeList = [
    {
        type: TrafficConfigType.SPH,
        title: "视频号获客",
        desc: "监控视频号账号，出现关键词立即寻找线索",
        icon: "📡",
        iconBg: "#FFF0F0",
    },
    {
        type: TrafficConfigType.Video,
        title: "视频截流",
        desc: "搜索同行视频，监控评论区寻找潜在客户",
        icon: "🎬",
        iconBg: "#FFF5F0",
    },
    {
        type: TrafficConfigType.GroupPurchase,
        title: "团购截流",
        desc: "搜索或指定团购，监控评论区寻找潜在客户",
        icon: "🛒",
        iconBg: "#E6F0FF",
    },
    {
        type: TrafficConfigType.City,
        title: "同城截流",
        desc: "挖掘同城视频评论，精准截流引客",
        icon: "🏙️",
        iconBg: "#E6F0FF",
    },
    {
        type: TrafficConfigType.Guard,
        title: "防封与频率设置",
        desc: "触达时间限制、防封控频率配置",
        icon: "⚙️",
        iconBg: "#E6F0FF",
    },
];

const ConfigCardInner = defineComponent({
    name: "ConfigCardInner",
    props: {
        title: { type: String, required: true },
        desc: { type: String, default: "" },
        icon: { type: String, default: "" },
        iconBg: { type: String, default: "#E6F0FF" },
    },
    setup(props, { slots }) {
        return () =>
            h("div", { class: "rounded-xl p-4 shadow-sm mb-3", style: "background:#ffffff;border:1px solid #F3F4F6" }, [
                h("div", { class: "flex items-center gap-3 mb-4" }, [
                    h(
                        "div",
                        {
                            class: "w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0 text-lg",
                            style: { background: props.iconBg },
                        },
                        props.icon,
                    ),
                    h("div", { class: "flex flex-col" }, [
                        h("span", { class: "text-sm font-extrabold", style: "color:#111827" }, props.title),
                        props.desc ? h("span", { class: "text-xs mt-0.5", style: "color:#9CA3AF" }, props.desc) : null,
                    ]),
                ]),
                slots.default?.(),
            ]);
    },
});

const TagListInner = defineComponent({
    name: "TagListInner",
    props: {
        items: { type: Array as () => string[], required: true },
        addText: { type: String, default: "添加" },
        defaultShowCount: { type: Number, default: 5 },
    },
    emits: ["add", "edit", "remove"],
    setup(props, { emit }) {
        const isExpanded = ref(false);
        const displayedItems = computed(() =>
            isExpanded.value ? props.items : props.items.slice(0, props.defaultShowCount),
        );
        watch(
            () => props.items.length,
            (n, o) => {
                if (n > o) isExpanded.value = true;
            },
        );
        return () =>
            h("div", { class: "flex flex-wrap gap-2" }, [
                ...displayedItems.value.map((item, index) =>
                    h(
                        "div",
                        {
                            key: index,
                            class: "inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full cursor-pointer",
                            style: "background:#F9FAFB;border:1px solid #E5E7EB",
                            onClick: () => emit("edit", index),
                        },
                        [
                            h("span", { class: "text-xs", style: "color:#374151" }, item),
                            h(
                                "div",
                                {
                                    class: "w-4 h-4 flex items-center justify-center rounded-full",
                                    style: "background:#D1D5DB",
                                    onClick: (e: any) => {
                                        e.stopPropagation();
                                        emit("remove", index);
                                    },
                                },
                                [h(Close, { style: "width:10px;height:10px;color:#fff" })],
                            ),
                        ],
                    ),
                ),
                h(
                    "div",
                    {
                        class: "inline-flex items-center gap-1 px-3 py-1.5 rounded-full cursor-pointer",
                        style: "border:2px dashed #BFDBFE;background:#EFF6FF",
                        onClick: () => emit("add"),
                    },
                    [
                        h(Plus, { style: "width:12px;height:12px;color:#0065fb" }),
                        h("span", { class: "text-xs font-medium text-primary" }, props.addText),
                    ],
                ),
                props.items.length > props.defaultShowCount
                    ? h(
                          "div",
                          {
                              class: "inline-flex items-center gap-0.5 px-3 py-1.5 rounded-full cursor-pointer",
                              style: "background:#F3F4F6",
                              onClick: () => {
                                  isExpanded.value = !isExpanded.value;
                              },
                          },
                          [
                              h(
                                  "span",
                                  { class: "text-xs", style: "color:#9CA3AF" },
                                  isExpanded.value ? "收起" : `+${props.items.length - props.defaultShowCount} 个`,
                              ),
                              h(isExpanded.value ? ArrowUp : ArrowDown, {
                                  style: "width:12px;height:12px;color:#9CA3AF",
                              }),
                          ],
                      )
                    : null,
            ]);
    },
});

const emit = defineEmits(["success", "close"]);
const popupRef = shallowRef<InstanceType<typeof Popup>>();

const listLoading = ref(false);
const settingLoading = ref(false);
const personId = ref("");
const currentType = ref<TrafficConfigType | null>(null);
const currentTypeItem = computed(() => typeList.find((t) => t.type === currentType.value) ?? null);

const grouponCommentKeywordInput = ref("");
const grouponNicknameInput = ref("");
const isGrouponCustomDistance = ref(false);
const grouponCustomDistanceInput = ref("");

const cityNicknameInput = ref("");
const cityCommentKeywordInput = ref("");
const isCityCustomDistance = ref(false);
const cityCustomDistanceInput = ref("");

interface ConfigData {
    acquisitionWords: string[];
    acquisitionLimit: number;
    acquisitionStrategy: number;
    interceptionSearchWords: string[];
    interceptionMatchWords: string[];
    interceptionLimit: number;
    interceptionStrategy: number;
    grouponTab: GrouponTab;
    grouponTypeKeyword: string;
    grouponDistance: number;
    commentNumber: number;
    grouponCommentKeywords: string[];
    grouponPublishDay: number;
    grouponCommentNum: number;
    grouponActions: GrouponAction[];
    grouponLikeType: number;
    grouponWatchSeconds: number;
    grouponReachInterval: number;
    grouponGender: number;
    grouponAgeMin: number;
    grouponAgeMax: number;
    grouponNicknameFilter: string[];
    cityActions: GrouponAction[];
    cityWatchSeconds: number;
    cityReachInterval: number;
    cityDistance: number;
    cityGenderFilter: number;
    cityAgeMin: number;
    cityAgeMax: number;
    cityVideoMatchNum: number;
    cityVideoCommentNum: number;
    cityCommentFansMin: number;
    cityCommentFansMax: number;
    cityFollowMin: number;
    cityFollowMax: number;
    cityNicknameFilter: string[];
    cityCommentKeywords: string[];
    contentPublishTime: number;
    commentPublishTime: number;
    messageNumber: number;
    cityCommentNumber: number;
    videoMessageNumber: number;
    cityMessageNumber: number;
    grouponMessageNumber: number;
    replyNumber: number;
}

const configData = reactive<ConfigData>({
    acquisitionWords: [],
    acquisitionLimit: 10,
    acquisitionStrategy: 1,
    interceptionSearchWords: [],
    interceptionMatchWords: [],
    interceptionLimit: 10,
    interceptionStrategy: 1,
    grouponTab: GrouponTab.Search,
    grouponTypeKeyword: "",
    grouponDistance: 0,
    commentNumber: 15,
    grouponCommentKeywords: [],
    grouponPublishDay: 7,
    grouponCommentNum: 1,
    grouponActions: [GrouponAction.Comment],
    grouponLikeType: 1,
    grouponWatchSeconds: 5,
    grouponReachInterval: 30,
    grouponGender: 0,
    grouponAgeMin: 18,
    grouponAgeMax: 60,
    grouponNicknameFilter: [],
    cityActions: [GrouponAction.Comment],
    cityWatchSeconds: 5,
    cityReachInterval: 30,
    cityDistance: 0,
    cityGenderFilter: 0,
    cityAgeMin: 18,
    cityAgeMax: 60,
    cityVideoMatchNum: 0,
    cityVideoCommentNum: 0,
    cityCommentFansMin: 0,
    cityCommentFansMax: 0,
    cityFollowMin: 0,
    cityFollowMax: 0,
    cityNicknameFilter: [],
    cityCommentKeywords: [],
    contentPublishTime: 1,
    commentPublishTime: 1,
    messageNumber: 15,
    cityCommentNumber: 15,
    videoMessageNumber: 15,
    cityMessageNumber: 15,
    grouponMessageNumber: 15,
    replyNumber: 1,
});

const grouponHasMutexSelected = computed(() =>
    GROUPON_MUTEX_ACTION_LIST.some((a) => configData.grouponActions.includes(a.value)),
);
const cityHasMutexSelected = computed(() =>
    GROUPON_MUTEX_ACTION_LIST.some((a) => configData.cityActions.includes(a.value)),
);

const toggleGrouponFreeAction = (val: GrouponAction) => {
    const idx = configData.grouponActions.indexOf(val);
    idx === -1 ? configData.grouponActions.push(val) : configData.grouponActions.splice(idx, 1);
};
const toggleGrouponMutexAction = (val: GrouponAction) => {
    const idx = configData.grouponActions.indexOf(val);
    if (idx !== -1) {
        configData.grouponActions.splice(idx, 1);
    } else {
        GROUPON_MUTEX_ACTION_LIST.forEach(({ value }) => {
            const i = configData.grouponActions.indexOf(value);
            if (i !== -1) configData.grouponActions.splice(i, 1);
        });
        configData.grouponActions.push(val);
    }
};
const toggleCityFreeAction = (val: GrouponAction) => {
    const idx = configData.cityActions.indexOf(val);
    idx === -1 ? configData.cityActions.push(val) : configData.cityActions.splice(idx, 1);
};
const toggleCityMutexAction = (val: GrouponAction) => {
    const idx = configData.cityActions.indexOf(val);
    if (idx !== -1) {
        configData.cityActions.splice(idx, 1);
    } else {
        GROUPON_MUTEX_ACTION_LIST.forEach(({ value }) => {
            const i = configData.cityActions.indexOf(value);
            if (i !== -1) configData.cityActions.splice(i, 1);
        });
        configData.cityActions.push(val);
    }
};

const handleGrouponSelectDistance = (val: number) => {
    isGrouponCustomDistance.value = false;
    configData.grouponDistance = val;
};
const handleGrouponCustomDistanceBlur = () => {
    const v = parseFloat(grouponCustomDistanceInput.value);
    if (!isNaN(v) && v > 0) configData.grouponDistance = v;
};
const handleCitySelectDistance = (val: number) => {
    isCityCustomDistance.value = false;
    configData.cityDistance = val;
};
const handleCityCustomDistanceBlur = () => {
    const v = parseFloat(cityCustomDistanceInput.value);
    if (!isNaN(v) && v > 0) configData.cityDistance = v;
};

const handleAddGrouponKeyword = () => {
    const v = grouponCommentKeywordInput.value.trim();
    if (!v) return;
    configData.grouponCommentKeywords.push(v);
    grouponCommentKeywordInput.value = "";
};
const handleEditGrouponKeyword = async (idx: number) => {
    const { value } = await ElMessageBox.prompt("修改关键词", "编辑", {
        inputValue: configData.grouponCommentKeywords[idx],
        confirmButtonText: "确定",
        cancelButtonText: "取消",
    }).catch(() => ({ value: null }));
    if (value !== null) configData.grouponCommentKeywords[idx] = value;
};
const handleAddGrouponNickname = () => {
    const v = grouponNicknameInput.value.trim();
    if (!v) return;
    configData.grouponNicknameFilter.push(v);
    grouponNicknameInput.value = "";
};
const handleEditGrouponNickname = async (idx: number) => {
    const { value } = await ElMessageBox.prompt("修改过滤词", "编辑", {
        inputValue: configData.grouponNicknameFilter[idx],
        confirmButtonText: "确定",
        cancelButtonText: "取消",
    }).catch(() => ({ value: null }));
    if (value !== null) configData.grouponNicknameFilter[idx] = value;
};
const handleAddCityNickname = () => {
    const v = cityNicknameInput.value.trim();
    if (!v) return;
    configData.cityNicknameFilter.push(v);
    cityNicknameInput.value = "";
};
const handleEditCityNickname = async (idx: number) => {
    const { value } = await ElMessageBox.prompt("修改过滤词", "编辑", {
        inputValue: configData.cityNicknameFilter[idx],
        confirmButtonText: "确定",
        cancelButtonText: "取消",
    }).catch(() => ({ value: null }));
    if (value !== null) configData.cityNicknameFilter[idx] = value;
};
const handleAddCityKeyword = () => {
    const v = cityCommentKeywordInput.value.trim();
    if (!v) return;
    configData.cityCommentKeywords.push(v);
    cityCommentKeywordInput.value = "";
};
const handleEditCityKeyword = async (idx: number) => {
    const { value } = await ElMessageBox.prompt("修改关键词", "编辑", {
        inputValue: configData.cityCommentKeywords[idx],
        confirmButtonText: "确定",
        cancelButtonText: "取消",
    }).catch(() => ({ value: null }));
    if (value !== null) configData.cityCommentKeywords[idx] = value;
};

type TagField = "acquisitionWords" | "interceptionSearchWords" | "interceptionMatchWords";
const handleAdd = async (field: TagField) => {
    const { value } = await ElMessageBox.prompt("请输入关键词", "添加", {
        confirmButtonText: "确定",
        cancelButtonText: "取消",
    }).catch(() => ({ value: null }));
    if (value?.trim()) (configData[field] as string[]).push(value.trim());
};
const handleEdit = async (field: TagField, idx: number) => {
    const { value } = await ElMessageBox.prompt("修改关键词", "编辑", {
        inputValue: (configData[field] as string[])[idx],
        confirmButtonText: "确定",
        cancelButtonText: "取消",
    }).catch(() => ({ value: null }));
    if (value !== null) (configData[field] as string[])[idx] = value;
};
const removeTag = (field: TagField, idx: number) => {
    (configData[field] as string[]).splice(idx, 1);
};

const enterSetting = async (type: TrafficConfigType) => {
    currentType.value = type;
    await loadSetting(type);
};
const backToList = () => {
    currentType.value = null;
};

const loadSetting = async (type: TrafficConfigType) => {
    settingLoading.value = true;
    try {
        const d = await getTrafficConfig({ id: personId.value });
        if (type === TrafficConfigType.SPH) {
            configData.acquisitionWords = d.clue_keywords ?? [];
            configData.acquisitionLimit = d.clue_max_number ?? 10;
            configData.acquisitionStrategy = d.clue_keyword_used_type ?? 1;
        } else if (type === TrafficConfigType.Video) {
            configData.interceptionSearchWords = d.acquire_keywords ?? [];
            configData.interceptionMatchWords = d.intercept_keywords ?? [];
            configData.interceptionLimit = d.intercept_max_number ?? 10;
            configData.interceptionStrategy = d.intercept_keyword_used_type ?? 1;
        } else if (type === TrafficConfigType.GroupPurchase) {
            // 团购：后端通常只回 group_buy_method，其余字段缺省时回退到默认示例（与 uniapp 一致）
            const g = d.group_buy_config ?? {};
            configData.grouponTab = g.group_buy_method ?? GrouponTab.Search;
            configData.grouponTypeKeyword = g.group_buy_keyword || "双人套餐";
            configData.grouponDistance = g.range ?? 0;
            configData.commentNumber = g.exec_number ?? 10;
            configData.grouponCommentKeywords =
                Array.isArray(g.comment_keywords) && g.comment_keywords.length
                    ? g.comment_keywords
                    : ["怎么买", "划算吗", "多少钱"];
            configData.grouponPublishDay = g.group_publish_day ?? 7;
            configData.grouponCommentNum = g.group_num_comment ?? 1;
            configData.grouponActions = normalizeCommentUserActions(g.interactive_action);
            configData.grouponLikeType = g.group_thumb_method ?? 1;
            configData.grouponWatchSeconds = g.view_video_time ?? 5;
            configData.grouponReachInterval = g.touch_interval ?? 30;
            configData.grouponGender = g.gender ?? 0;
            configData.grouponNicknameFilter =
                Array.isArray(g.filter_nickname) && g.filter_nickname.length ? g.filter_nickname : ["同行", "客服"];
        } else if (type === TrafficConfigType.City) {
            const c = d.same_city_config ?? {};
            configData.cityActions = normalizeCommentUserActions(c.interactive_action);
            configData.cityWatchSeconds = c.view_video_time ?? 5;
            configData.cityReachInterval = c.touch_interval ?? 30;
            configData.cityDistance = c.range ?? 0;
            configData.cityGenderFilter = c.gender ?? 0;
            configData.cityAgeMin = c.age_range?.min ?? 18;
            configData.cityAgeMax = c.age_range?.max ?? 60;
            configData.cityVideoMatchNum = c.filter_video_thumb_num ?? 0;
            configData.cityVideoCommentNum = c.filter_video_comment_num ?? 0;
            configData.cityCommentFansMin = c.filter_comment_fans?.min ?? 0;
            configData.cityCommentFansMax = c.filter_comment_fans?.max ?? 0;
            configData.cityFollowMin = c.filter_comment_follow?.min ?? 0;
            configData.cityFollowMax = c.filter_comment_follow?.max ?? 0;
            configData.cityNicknameFilter = c.filter_nickname ?? [];
            configData.cityCommentKeywords = c.comment_keywords ?? [];
        } else if (type === TrafficConfigType.Guard) {
            configData.contentPublishTime = d.content_publish_day === 0 ? -1 : d.content_publish_day ?? 1;
            configData.commentPublishTime = d.comment_publish_day === 0 ? -1 : d.comment_publish_day ?? 1;
            configData.messageNumber = d.message_number ?? 15;
            configData.cityCommentNumber = d.comment_number ?? 15;
            configData.grouponMessageNumber = d.group_cutoff_number ?? 15;
            configData.videoMessageNumber = d.video_cutoff_number ?? 15;
            configData.cityMessageNumber = d.city_cutoff_number ?? 15;
            configData.replyNumber = d.reply_number === 0 ? 2 : 1;
        }
    } finally {
        settingLoading.value = false;
    }
};

const handleSave = async () => {
    const type = currentType.value;
    let params: Record<string, any> = { persona_id: personId.value };
    if (type === TrafficConfigType.SPH) {
        params = {
            ...params,
            clue_keywords: configData.acquisitionWords,
            clue_max_number: configData.acquisitionLimit,
            clue_keyword_used_type: configData.acquisitionStrategy,
        };
    } else if (type === TrafficConfigType.Video) {
        params = {
            ...params,
            acquire_keywords: configData.interceptionSearchWords,
            intercept_keywords: configData.interceptionMatchWords,
            intercept_max_number: configData.interceptionLimit,
            intercept_keyword_used_type: configData.interceptionStrategy,
        };
    } else if (type === TrafficConfigType.GroupPurchase) {
        params = {
            ...params,
            group_buy_config: {
                group_buy_method: configData.grouponTab,
                group_buy_keyword: configData.grouponTypeKeyword,
                range: configData.grouponDistance,
                exec_number: configData.commentNumber,
                comment_keywords: configData.grouponCommentKeywords,
                group_publish_day: configData.grouponPublishDay,
                group_num_comment: configData.grouponCommentNum,
                interactive_action: filterCommentUserActions(configData.grouponActions),
                group_thumb_method: configData.grouponLikeType,
                view_video_time: configData.grouponWatchSeconds,
                touch_interval: configData.grouponReachInterval,
                gender: configData.grouponGender,
                filter_nickname: configData.grouponNicknameFilter,
            },
        };
    } else if (type === TrafficConfigType.City) {
        params = {
            ...params,
            same_city_config: {
                interactive_action: filterCommentUserActions(configData.cityActions),
                view_video_time: configData.cityWatchSeconds,
                touch_interval: configData.cityReachInterval,
                range: configData.cityDistance,
                gender: configData.cityGenderFilter,
                age_range: { min: configData.cityAgeMin, max: configData.cityAgeMax },
                filter_video_thumb_num: configData.cityVideoMatchNum,
                filter_video_comment_num: configData.cityVideoCommentNum,
                filter_comment_fans: { min: configData.cityCommentFansMin, max: configData.cityCommentFansMax },
                filter_comment_follow: { min: configData.cityFollowMin, max: configData.cityFollowMax },
                filter_nickname: configData.cityNicknameFilter,
                comment_keywords: configData.cityCommentKeywords,
            },
        };
    } else if (type === TrafficConfigType.Guard) {
        params = {
            ...params,
            content_publish_day: configData.contentPublishTime === -1 ? 0 : configData.contentPublishTime,
            comment_publish_day: configData.commentPublishTime === -1 ? 0 : configData.commentPublishTime,
            message_number: configData.messageNumber,
            comment_number: configData.cityCommentNumber,
            video_cutoff_number: configData.videoMessageNumber,
            city_cutoff_number: configData.cityMessageNumber,
            group_cutoff_number: configData.grouponMessageNumber,
            reply_number: configData.replyNumber === 2 ? 0 : 1,
        };
    }
    await updateTrafficConfig(params);
    emit("success");
    backToList();
};

const { isLock, lockFn } = useLockFn(handleSave);

const open = (id: string) => {
    personId.value = id;
    currentType.value = null;
    popupRef.value?.open();
};
const close = () => {
    currentType.value = null;
    emit("close");
};

defineExpose({ open });
</script>

<style scoped>
.page-container {
    position: relative;
    overflow: hidden;
    min-height: 200px;
}
.page-screen {
    width: 100%;
    transition: transform 0.32s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.32s cubic-bezier(0.4, 0, 0.2, 1);
}
.page-screen--active {
    transform: translateX(0);
    opacity: 1;
    position: relative;
}
.page-screen--exit {
    transform: translateX(-28%);
    opacity: 0;
    position: absolute;
    top: 0;
    left: 0;
    pointer-events: none;
}
.page-screen--enter {
    transform: translateX(100%);
    opacity: 0;
    position: absolute;
    top: 0;
    left: 0;
    pointer-events: none;
}
.back-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 13px;
    color: #6b7280;
    padding: 0 8px 0 4px;
    border-radius: 8px;
    transition: all 0.2s;
}
.back-btn:hover {
    background: #f3f4f6;
    color: #374151;
}
.back-btn-enter-active,
.back-btn-leave-active {
    transition: opacity 0.2s, transform 0.2s;
}
.back-btn-enter-from {
    opacity: 0;
    transform: translateX(-8px);
}
.back-btn-leave-to {
    opacity: 0;
    transform: translateX(-8px);
}
.section-block {
    border: 1px solid #f0f2f5;
    border-radius: 12px;
    padding: 16px;
    background: #fafbfc;
}
.section-title {
    font-size: 13px;
    font-weight: 700;
    color: #0d1117;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.section-title::before {
    content: "";
    display: inline-block;
    width: 3px;
    height: 14px;
    background: #0065fb;
    border-radius: 99px;
    flex-shrink: 0;
}
.field-label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: #9ca3af;
    margin-bottom: 8px;
}
</style>
