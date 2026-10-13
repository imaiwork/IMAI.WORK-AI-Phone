<template>
    <view>
        <!-- 资源类型主导航（始终显示）-->
        <view class="resource-tabs">
            <view
                v-for="tab in state.materialSubTabs"
                :key="tab.key"
                class="resource-tab active:opacity-70"
                :class="state.activeMaterialTab === tab.key ? 'resource-tab--active' : ''"
                @click="actions.switchMaterialTab(tab.key)">
                <text class="resource-tab-label">{{ tab.label }}</text>
                <text class="resource-tab-count">{{ state.materialTabCounts[tab.key] ?? 0 }}</text>
            </view>
        </view>

        <!-- 素材内容 -->
        <template v-if="state.activeMaterialTab === MaterialTabEnum.COMPOSE">
            <!-- 剪辑原片库 / 成品直发库是「在看哪个库」的浏览筛选，不落库，留在页面上 -->
            <view class="mb-[26rpx]">
                <view class="lib-seg lib-seg--materials">
                    <view
                        v-for="option in state.materialPublishModes"
                        :key="option.value"
                        class="lib-seg-item"
                        :class="state.publishMode === option.value ? 'lib-seg-item--on' : ''"
                        @click="actions.switchPublishMode(option.value)">
                        <text
                            class="lib-seg-label line-clamp-1">
                            {{ option.title }}
                        </text>
                    </view>
                </view>
            </view>
            <view v-if="state.publishMode === 1" class="material-mode-line">
                <text>用于 AI 剪辑合成</text>
                <!-- 素材模式是真设置（落到人设 material_use_mode），连同原图硬规格收进半屏 -->
                <view class="lib-entry-btn active:opacity-70" @click="showLibrarySetting = true">
                    <u-icon name="setting" color="#0761D9" size="30"></u-icon>
                    <text class="line-clamp-1">{{ currentKindLabel }}</text>
                    <u-icon name="arrow-right" color="#0761D9" size="26"></u-icon>
                </view>
            </view>
            <view v-else class="material-mode-line">
                <text>成品直接使用，不参与剪辑合成</text>
            </view>

            <!-- 量化门槛：告诉用户还差多少，别让他猜 -->
            <view v-if="state.publishMode === 1 && (state.materialShortage ?? 0) > 0" class="mat-gap">
                <view class="flex items-center gap-[16rpx] min-w-0">
                    <u-icon name="info-circle" color="#936114" size="32" class="shrink-0"></u-icon>
                    <text>剪辑素材还差 {{ state.materialShortage }} 段</text>
                </view>
                <text class="shrink-0 tabular-nums">
                    {{ state.materialMin - (state.materialShortage ?? 0) }} / {{ state.materialMin }}
                </text>
            </view>

            <view v-if="state.publishMode === 1" class="mb-[26rpx]">
                <view class="lib-seg">
                    <view
                        class="lib-seg-item"
                        :class="state.materialFolderView === 'all' ? 'lib-seg-item--on' : ''"
                        @click="actions.switchMaterialScope('all')">
                        <text class="lib-seg-label">全部</text>
                    </view>
                    <view
                        class="lib-seg-item"
                        :class="state.materialFolderView === 'groups' ? 'lib-seg-item--on' : ''"
                        @click="actions.switchMaterialScope('groups')">
                        <text class="lib-seg-label">分组</text>
                    </view>
                </view>
            </view>
            <view
                v-if="state.publishMode === 1 && state.materialFolderId !== null"
                class="mat-folder-back active:opacity-70"
                @click="actions.backMaterialFolder()">
                <u-icon name="arrow-left" color="#0761D9" size="28"></u-icon>
                <text class="line-clamp-1">{{ state.materialFolderName || "未分组" }}</text>
            </view>

            <view class="material-toolbar">
                <view v-if="!state.showingMaterialGroupList" class="material-toolbar-filters">
                    <view
                        v-for="(filter, index) in state.materialFilters"
                        :key="filter.name"
                        class="material-filter active:opacity-70"
                        :class="state.activeMaterialFilter === index ? 'material-filter--active' : ''"
                        @click="actions.materialFilter(index)">
                        <text class="material-filter-label">{{ filter.name }}</text>
                    </view>
                </view>
                <view class="resource-action active:opacity-70" @click="handleMaterialActions()">
                    <text>更多</text>
                </view>
            </view>

            <view v-if="state.showingMaterialGroupList" class="mat-group-list">
                <view class="mat-group-create active:opacity-70" @click="openGroupName()">
                    <u-icon name="plus" color="#0761D9" size="28"></u-icon>
                    <text>新建分组</text>
                </view>
                <view
                    v-for="folder in state.materialFolders"
                    :key="folder.id"
                    class="mat-group-row active:opacity-70"
                    @click="actions.openMaterialFolder(folder)">
                    <view class="min-w-0 flex-1">
                        <text class="mat-group-name line-clamp-1">{{ folder.name }}</text>
                        <text class="mat-group-count">{{ folder.material_count }} 个素材</text>
                    </view>
                    <view class="mat-group-manage active:opacity-70" @click.stop="manageMaterialGroup(folder)">
                        <text>管理</text>
                    </view>
                </view>
                <view
                    class="mat-group-row active:opacity-70"
                    @click="actions.openMaterialFolder({ id: 0, name: '未分组' })">
                    <view class="min-w-0 flex-1">
                        <text class="mat-group-name">未分组</text>
                        <text class="mat-group-count">{{ state.ungroupedCount }} 个素材</text>
                    </view>
                </view>
            </view>

            <view
                v-if="state.publishMode === 1 && !state.showingMaterialGroupList && state.hasOverusedMaterial"
                class="bg-[#FFF7ED] rounded-[28rpx] px-[26rpx] py-[22rpx] flex gap-[16rpx] mb-[18rpx] border border-solid border-[#FED7AA]">
                <u-icon name="warning" color="#F97316" size="28" class="shrink-0 mt-[2rpx]"></u-icon>
                <text class="text-xs text-[#EA580C] leading-relaxed">
                    有素材使用已达3次，建议及时替换，避免影响内容流量权重。
                </text>
            </view>

            <view
                v-if="state.publishMode === 1 && !state.showingMaterialGroupList && !state.hasSlicingMaterial && state.failedMaterialCount > 0"
                class="bg-[#FFEAEC] rounded-[28rpx] px-[26rpx] py-[22rpx] flex items-center gap-[16rpx] mb-[18rpx] border border-solid border-[#FCA5A5]">
                <u-icon name="close-circle" color="#F56C6C" size="28" class="shrink-0"></u-icon>
                <text class="flex-1 min-w-0 text-xs text-[#CC2330] leading-relaxed">
                    有 {{ state.failedMaterialCount }} 条视频分割失败，无法用于合成，建议删除后重新上传。
                </text>
                <view
                    class="shrink-0 px-[22rpx] py-[12rpx] rounded-full bg-[#F56C6C] active:opacity-80"
                    @click="actions.deleteFailedSlices()">
                    <text class="text-[22rpx] font-semibold text-white">一键删除</text>
                </view>
            </view>

            <!-- 切割仅针对视频：有失败且无切割中时不展示；删除后无失败时靠 statistics 为空收口 -->
            <slice-progress
                v-if="
                    state.publishMode === 1 &&
                    !state.showingMaterialGroupList &&
                    state.materialFilters[state.activeMaterialFilter]?.value !== 2 &&
                    state.hasSlicingTask &&
                    (state.hasSlicingMaterial || state.failedMaterialCount === 0)
                "
                :statistics="state.sliceStatistics" />

            <view v-if="!state.showingMaterialGroupList && state.materialList.length" class="resource-grid">
                <view
                    v-for="item in state.materialList"
                    :key="item.id"
                    class="material-card"
                    :class="[
                        state.isMaterialSelected(item.id) ? 'border-primary' : 'border-[#E1E7EF]',
                        item.source === 'square' && item.alive === false ? 'sq-dead' : '',
                    ]"
                    @click="state.batchDeleteMode && actions.toggleMaterialSelected(item.id)">
                    <view
                        v-if="state.batchDeleteMode"
                        class="absolute top-[14rpx] right-[14rpx] z-10 w-[40rpx] h-[40rpx] rounded-full border-[4rpx] border-solid flex items-center justify-center"
                        :class="
                            state.isMaterialSelected(item.id)
                                ? 'bg-primary border-primary'
                                : 'bg-white border-[#C8C9CC]'
                        "
                        @click.stop="actions.toggleMaterialSelected(item.id)">
                        <u-icon
                            v-if="state.isMaterialSelected(item.id)"
                            name="checkbox-mark"
                            color="#FFFFFF"
                            size="24"></u-icon>
                    </view>
                    <view
                        class="material-cover"
                        @click.stop="
                            state.batchDeleteMode
                                ? actions.toggleMaterialSelected(item.id)
                                : actions.previewMaterial(item)
                        ">
                        <view
                            v-if="state.isMaterialSlicing(item)"
                            class="absolute inset-0 flex flex-col items-center justify-center gap-[12rpx] bg-[#EEF3FB]">
                            <view class="mat-slice-spinner"></view>
                            <text class="text-[20rpx] font-semibold text-primary">切割中…</text>
                        </view>
                        <view
                            v-else-if="state.isMaterialSliceFailed(item)"
                            class="absolute inset-0 flex flex-col items-center justify-center gap-[10rpx] bg-[#FFEAEC]">
                            <image
                                v-if="item.thumbnail_url"
                                :src="item.thumbnail_url"
                                class="absolute inset-0 w-full h-full opacity-30"
                                mode="aspectFill"></image>
                            <view class="relative z-[1] flex flex-col items-center gap-[10rpx]">
                                <u-icon name="close-circle" color="#F56C6C" size="40"></u-icon>
                                <text class="text-[20rpx] font-semibold text-[#F56C6C]">
                                    {{ item.slice_status_text || "分割失败" }}
                                </text>
                            </view>
                        </view>
                        <template v-else>
                            <image
                                v-if="item.thumbnail_url || item.material_type === 2"
                                :src="item.thumbnail_url || item.file_url"
                                class="absolute inset-0 w-full h-full"
                                mode="aspectFill"></image>
                        </template>
                        <view class="absolute top-[14rpx] left-[14rpx] flex flex-col gap-[8rpx]">
                            <text
                                class="text-[18rpx] font-bold text-white px-[10rpx] py-[4rpx] rounded-[8rpx] w-fit"
                                style="background: rgba(0, 0, 0, 0.55)">
                                {{ item.material_type === 1 ? "视频" : item.material_type === 2 ? "图片" : "素材" }}
                            </text>
                            <text
                                v-if="item.source === 'square'"
                                class="text-[18rpx] font-bold text-white px-[10rpx] py-[4rpx] rounded-[8rpx]"
                                :style="
                                    item.alive === false
                                        ? 'background: rgba(144, 147, 153, 0.9)'
                                        : 'background: rgba(64, 158, 255, 0.92)'
                                ">
                                {{ item.alive === false ? "已下架" : "广场" }}
                            </text>
                            <text
                                v-if="item.grab_type === 1"
                                class="text-[18rpx] font-bold text-white px-[10rpx] py-[4rpx] rounded-[8rpx]"
                                style="background: rgba(124, 58, 237, 0.82)">
                                AI抓取
                            </text>
                            <!-- 原图模式入库：合成时不压缩不适配 -->
                            <text
                                v-if="Number(item.keep_original) === 1"
                                class="text-[18rpx] font-bold text-white px-[10rpx] py-[4rpx] rounded-[8rpx] w-fit"
                                style="background: rgba(180, 83, 9, 0.85)">
                                原图
                            </text>
                        </view>
                        <view
                            v-if="
                                state.publishMode === 1 &&
                                item.use_num >= 3 &&
                                !state.batchDeleteMode &&
                                !state.isMaterialSliceFailed(item)
                            "
                            class="absolute bottom-[14rpx] right-[14rpx] w-[36rpx] h-[36rpx] rounded-full bg-[#FBBF24] flex items-center justify-center">
                            <u-icon name="warning" color="#FFFFFF" size="20"></u-icon>
                        </view>
                        <view
                            v-if="
                                item.material_type === 1 &&
                                item.alive !== false &&
                                !state.isMaterialSlicing(item) &&
                                !state.isMaterialSliceFailed(item)
                            "
                            class="absolute inset-0 flex items-center justify-center"
                            @click.stop="
                                state.batchDeleteMode
                                    ? actions.toggleMaterialSelected(item.id)
                                    : actions.previewMaterial(item)
                            ">
                            <view
                                class="w-[64rpx] h-[64rpx] rounded-full flex items-center justify-center"
                                style="background: rgba(255, 255, 255, 0.3)">
                                <u-icon name="play-right-fill" color="#FFFFFF" size="28"></u-icon>
                            </view>
                        </view>
                        <text
                            v-if="item.duration && !state.isMaterialSliceFailed(item)"
                            class="absolute left-[14rpx] bottom-[14rpx] text-[20rpx] text-white font-mono">
                            {{ formatAudioTime(item.duration) }}
                        </text>
                    </view>
                    <view
                        v-if="!state.batchDeleteMode && (state.publishMode === 2 || !state.isMaterialSlicing(item))"
                        class="material-card-menu active:opacity-70"
                        :aria-label="state.publishMode === 1 ? '素材操作' : '移除素材'"
                        @click.stop="state.publishMode === 1 ? actions.moreMaterial(item) : actions.removeMaterial(item)">
                        <u-icon :name="state.publishMode === 1 ? 'more-dot-fill' : 'trash'" color="#526B88" size="34"></u-icon>
                    </view>
                    <view class="material-card-meta">
                        <template v-if="state.publishMode === 1">
                            <view class="flex items-center justify-between mb-[10rpx]">
                                <text class="text-[24rpx] font-semibold text-[#26364E] line-clamp-1">
                                    {{ item.material_name }}
                                </text>
                            </view>
                            <view class="flex items-center justify-between">
                                <text
                                    v-if="state.isMaterialSlicing(item)"
                                    class="text-[20rpx] font-semibold px-[12rpx] py-[4rpx] rounded-[8rpx] text-primary bg-[#E6EFFF]">
                                    切割中
                                </text>
                                <text
                                    v-else-if="state.isMaterialSliceFailed(item)"
                                    class="text-[20rpx] font-semibold px-[12rpx] py-[4rpx] rounded-[8rpx] text-[#F56C6C] bg-[#FFEAEC]">
                                    {{ item.slice_status_text || "分割失败" }}
                                </text>
                                <text
                                    v-else-if="item.source !== 'square'"
                                    class="text-[20rpx] font-semibold px-[12rpx] py-[4rpx] rounded-[8rpx]"
                                    :class="
                                        item.use_num >= 3
                                            ? 'text-[#CC7A00] bg-[#FFF7ED]'
                                            : item.use_num > 0
                                            ? 'text-[#19BE6B] bg-[#F0FDF4]'
                                            : 'text-[#999999] bg-[#F2F3F5]'
                                    ">
                                    {{ item.use_num > 0 ? `已用${item.use_num}次` : "未使用" }}
                                </text>
                                <text class="ml-auto text-[22rpx] text-[#748297]">
                                    {{ state.formatMaterialTime(item.create_time) }}
                                </text>
                            </view>
                        </template>
                        <view v-else>
                            <text class="text-[24rpx] font-semibold text-[#26364E] line-clamp-1 block">
                                {{ item.material_name }}
                            </text>
                            <text class="block mt-[8rpx] text-[22rpx] text-[#748297]">
                                成品素材 · {{ state.directReuseLabel }}
                            </text>
                            <view class="mt-[8rpx]">
                                <text class="text-[22rpx] text-[#748297]">
                                    {{ state.formatMaterialTime(item.create_time) }}
                                </text>
                            </view>
                        </view>
                    </view>
                </view>
            </view>

            <!-- 空态是这一步的操作台：说清要传到什么程度、什么样的能用 -->
            <resource-empty-state
                v-else-if="!state.showingMaterialGroupList && !state.materialLoading && state.materialList.length === 0"
                :icon="state.publishMode === 1 ? 'play-circle' : 'play-right-fill'"
                :title="state.publishMode === 1 ? '先准备一组剪辑素材' : '添加可直接使用的成品'"
                :description="
                    state.publishMode === 1
                        ? `从相册上传生活或工作片段，也可从已有素材库选取。${state.materialKind === MaterialKindEnum.ORIGINAL ? '当前为原图模式，请先确认文件规格。' : ''}`
                        : `选择已制作好的视频或图片，不进行转码或切割。${state.directReuseLabel}。`
                "
                :action-text="state.publishMode === 1 ? '添加素材' : '添加成品'"
                @action="state.publishMode === 1 ? actions.upload() : actions.openDirectUpload()">
                <template #footer>
                    <view class="mat-empty-more active:opacity-70" @click="showMaterialRequirements = true">
                        <text>素材要求</text>
                        <u-icon name="arrow-right" color="#0761D9" size="28"></u-icon>
                    </view>
                </template>
            </resource-empty-state>
            <view v-if="!state.showingMaterialGroupList" class="py-[24rpx] flex items-center justify-center gap-[12rpx]">
                <u-loading v-if="state.materialLoading" mode="circle" size="28" color="#999999"></u-loading>
                <text v-if="state.materialLoading" class="text-xs text-[#999999]">加载中...</text>
                <text
                    v-else-if="state.materialFinished && state.materialList.length"
                    class="text-[22rpx] text-[#C4C9D4]">
                    已加载全部
                </text>
            </view>

            <!-- 批量删除模式下为固定操作栏预留高度，避免遮挡列表底部 -->
            <view v-if="state.batchDeleteMode" class="h-[150rpx]"></view>

            <view v-if="state.batchDeleteMode" class="mat-batch-bar">
                <view class="flex items-center gap-[10rpx] shrink-0" @click="actions.toggleSelectAll()">
                    <view
                        class="w-[40rpx] h-[40rpx] rounded-full border-[4rpx] border-solid flex items-center justify-center"
                        :class="
                            state.isAllMaterialSelected ? 'bg-primary border-primary' : 'bg-white border-[#C8C9CC]'
                        ">
                        <u-icon
                            v-if="state.isAllMaterialSelected"
                            name="checkbox-mark"
                            color="#FFFFFF"
                            size="24"></u-icon>
                    </view>
                    <text class="text-xs font-semibold text-[#666666]">全选</text>
                </view>
                <text class="text-xs text-[#999999]"> 已选 {{ state.selectedMaterialCount }} 项 </text>
                <view class="flex items-center gap-[14rpx] ml-auto shrink-0">
                    <view
                        class="text-xs text-[#999999] bg-[#F4F6FA] border border-solid border-[#E2E2E2] rounded-full px-[30rpx] py-[14rpx]"
                        @click="actions.cancelBatchDelete()">
                        取消
                    </view>
                    <view
                        v-if="state.publishMode === 1"
                        class="text-xs text-[#0761D9] bg-[#E8F1FF] rounded-full px-[30rpx] py-[14rpx]"
                        @click="openMoveSheet()">
                        移动至
                    </view>
                    <view
                        class="text-xs text-white bg-[#F56C6C] rounded-full px-[30rpx] py-[14rpx]"
                        @click="actions.confirmBatchDelete()">
                        删除所选
                    </view>
                </view>
            </view>
        </template>

        <template v-if="state.activeMaterialTab === MaterialTabEnum.AVATAR">
            <view class="resource-heading">
                <view class="resource-heading-copy">
                    <text class="resource-section-title">数字人形象</text>
                    <text class="resource-meta">{{ state.avatars.length }} 个 · 每个形象分别绑定声音</text>
                </view>
            </view>
            <view v-if="state.avatars.length" class="resource-grid">
                <view
                    v-for="item in state.avatars"
                    :key="item.id"
                    class="avatar-panel">
                    <view class="avatar-cover">
                        <image :src="item.pic" class="absolute inset-0 w-full h-full" mode="aspectFill"></image>
                        <!-- 冻结/失效的绑定照样列出来，遮罩说清原因，别让人以为形象自己没了 -->
                        <view v-if="item.available === false" class="asset-mask">
                            <text class="asset-mask-label">不可用</text>
                        </view>
                    </view>
                    <text class="resource-item-title line-clamp-1">{{ item.name }}</text>
                    <text
                        v-if="item.available === false"
                        class="asset-unavailable block mt-[8rpx] line-clamp-2">
                        {{ item.unavailable_reason }}
                    </text>
                    <text v-else class="resource-meta block mt-[8rpx] line-clamp-1">{{ avatarVoiceLabel(item) }}</text>
                    <!-- 形象本身被冻结/失效时换什么声音都救不回来，收掉入口，别让用户白点 -->
                    <view
                        v-if="item.anchor_available !== false"
                        class="resource-button resource-button--soft avatar-bind active:opacity-70"
                        @click="actions.openVoiceForAvatar(item)">
                        <text class="resource-button-label">绑定声音</text>
                    </view>
                    <view v-else class="resource-button resource-button--soft avatar-bind avatar-bind--off">
                        <text class="resource-button-label">暂不可绑定</text>
                    </view>
                    <view class="flex items-center justify-between gap-[8rpx]">
                        <text class="resource-meta">已用 {{ item.use_count || 0 }} 次</text>
                        <view class="resource-button resource-button--text avatar-remove active:opacity-70" @click="actions.removeAvatar(item)">
                            <text class="resource-button-label">移除</text>
                        </view>
                    </view>
                </view>

                <view class="avatar-add active:opacity-70" @click="actions.addAvatar()">
                    <u-icon name="plus" color="#54739B" size="40"></u-icon>
                    <text>添加形象</text>
                </view>
            </view>
            <resource-empty-state
                v-else
                icon="account"
                title="先添加一个数字人形象"
                description="从已有形象中选择加入，再为每个形象绑定声音。"
                action-text="添加形象"
                @action="actions.addAvatar()" />
            <text v-if="state.avatars.length" class="resource-meta block mt-[24rpx]">加入多个形象后，系统可按内容匹配使用；每个形象的声音独立配置。</text>
        </template>

        <template v-if="state.activeMaterialTab === MaterialTabEnum.VOICE">
            <view class="resource-heading">
                <view class="resource-heading-copy">
                    <text class="resource-section-title">人设音色库</text>
                    <text class="resource-meta">{{ state.voices.length }} 个音色</text>
                </view>
                <view class="resource-button resource-button--outline active:opacity-70" @click="actions.addVoice()">
                    <text class="resource-button-label">添加音色</text>
                </view>
            </view>
            <view v-if="state.voices.length" class="resource-list">
                <view
                    v-for="item in state.voices"
                    :key="item.voice_id"
                    class="resource-panel">
                    <view class="resource-list-row">
                        <view class="flex-1 min-w-0">
                            <text class="resource-item-title line-clamp-1">{{ item.name }}</text>
                            <text
                                v-if="item.available === false"
                                class="asset-unavailable mt-[10rpx] block line-clamp-2">
                                {{ item.unavailable_reason }}
                            </text>
                            <text v-else class="resource-meta mt-[10rpx] block">
                                {{ item.create_time ? `${state.formatMaterialTime(item.create_time)} 添加` : "暂无添加时间" }}
                            </text>
                        </view>
                        <view
                            class="resource-button resource-button--soft resource-button--compact active:opacity-70"
                            @click.stop="actions.playVoice(item)">
                            <text class="resource-button-label">{{ state.isCurrentPlaying(item.voice_id) ? "暂停" : "试听" }}</text>
                        </view>
                    </view>
                    <view class="flex items-center justify-between gap-[24rpx]">
                        <view class="flex-1 min-w-0">
                            <text class="resource-meta block">可在数字人中绑定此音色</text>
                            <text class="resource-meta block">已使用 {{ item.use_count || 0 }} 次</text>
                        </view>
                        <view class="resource-button resource-button--text active:opacity-70" @click="actions.removeVoice(item)">
                            <text class="resource-button-label">移除</text>
                        </view>
                    </view>
                </view>
            </view>
            <resource-empty-state
                v-else
                icon="mic"
                title="还没有音色"
                description="从已有音色中选择，可一次加入多个。"
                action-text="添加音色"
                @action="actions.addVoice()" />
        </template>

        <template v-if="state.activeMaterialTab === MaterialTabEnum.MUSIC">
            <view class="resource-heading">
                <view class="resource-heading-copy">
                    <text class="resource-section-title">音乐库</text>
                    <text class="resource-meta">{{ state.materialTabCounts[MaterialTabEnum.MUSIC] ?? state.musicList.length }} 首</text>
                </view>
                <view class="resource-button resource-button--outline active:opacity-70" @click="actions.uploadMusic()">
                    <text class="resource-button-label">上传音乐</text>
                </view>
            </view>
            <text class="resource-meta block mb-[24rpx]">自定义配乐支持 MP3 / WAV，AI 剪辑时可从音乐库中随机取用。</text>

            <view v-if="state.musicList.length" class="flex items-center justify-between gap-[24rpx] mb-[24rpx]">
                <text class="resource-meta">我的配乐</text>
                <view
                    class="resource-button resource-button--text active:opacity-70"
                    @click="actions.toggleMusicBatch()">
                    <text class="resource-button-label">{{ state.musicBatchMode ? "完成管理" : "批量管理" }}</text>
                </view>
            </view>

            <view v-if="state.musicList.length" class="resource-list">
                <view
                    v-for="item in state.musicList"
                    :key="item.id"
                    class="resource-panel border-[2rpx] border-solid"
                    :class="state.isMusicSelected(item.id) ? 'border-primary' : 'border-[transparent]'"
                    @click="state.musicBatchMode && actions.toggleMusicSelected(item.id)">
                    <view class="resource-list-row">
                        <view
                            v-if="state.musicBatchMode"
                            class="w-[40rpx] h-[40rpx] rounded-full border-[4rpx] border-solid flex items-center justify-center shrink-0"
                            :class="
                                state.isMusicSelected(item.id) ? 'bg-primary border-primary' : 'bg-white border-[#C8C9CC]'
                            "
                            @click.stop="actions.toggleMusicSelected(item.id)">
                            <u-icon
                                v-if="state.isMusicSelected(item.id)"
                                name="checkbox-mark"
                                color="#FFFFFF"
                                size="24"></u-icon>
                        </view>
                        <view class="flex-1 min-w-0">
                            <text class="resource-item-title line-clamp-1">
                                {{ item.material_name || "未命名音乐" }}
                            </text>
                            <text v-if="musicExtLabel(item.file_url)" class="resource-meta block mt-[10rpx]">{{ musicExtLabel(item.file_url) }}</text>
                        </view>
                        <view v-if="!state.musicBatchMode" class="flex items-center gap-[12rpx] shrink-0">
                            <view class="resource-button resource-button--soft resource-button--compact active:opacity-70" @click.stop="actions.playMusic(item)">
                                <text class="resource-button-label">{{ state.isCurrentMusicPlaying(item.id) ? "暂停" : "试听" }}</text>
                            </view>
                            <view class="resource-button resource-button--text resource-button--compact active:opacity-70" @click.stop="actions.removeMusic(item)">
                                <text class="resource-button-label">移出</text>
                            </view>
                        </view>
                    </view>
                </view>
            </view>

            <resource-empty-state
                v-else-if="!state.musicLoading"
                icon="volume-up"
                title="还没有自定义配乐"
                description="可从聊天记录、素材库或素材组选择音乐。"
                action-text="上传音乐"
                @action="actions.uploadMusic()" />

            <view v-if="state.musicLoading" class="py-[40rpx] flex justify-center">
                <text class="text-xs text-[#999999]">加载中...</text>
            </view>

            <view v-if="state.musicBatchMode" class="h-[150rpx]"></view>
            <view v-if="state.musicBatchMode" class="mat-batch-bar">
                <view class="flex items-center gap-[10rpx] shrink-0" @click="actions.toggleSelectAllMusic()">
                    <view
                        class="w-[40rpx] h-[40rpx] rounded-full border-[4rpx] border-solid flex items-center justify-center"
                        :class="state.isAllMusicSelected ? 'bg-primary border-primary' : 'bg-white border-[#C8C9CC]'">
                        <u-icon v-if="state.isAllMusicSelected" name="checkbox-mark" color="#FFFFFF" size="24"></u-icon>
                    </view>
                    <text class="text-xs font-semibold text-[#666666]">全选</text>
                </view>
                <text class="text-xs text-[#999999]"> 已选 {{ state.selectedMusicCount }} 项 </text>
                <view class="flex items-center gap-[14rpx] ml-auto shrink-0">
                    <view
                        class="text-xs text-[#999999] bg-[#F4F6FA] border border-solid border-[#E2E2E2] rounded-full px-[30rpx] py-[14rpx]"
                        @click="actions.cancelMusicBatch()">
                        取消
                    </view>
                    <view
                        class="text-xs text-white bg-[#F56C6C] rounded-full px-[30rpx] py-[14rpx]"
                        @click="actions.confirmMusicBatchDelete()">
                        移出所选
                    </view>
                </view>
            </view>
        </template>

        <template v-if="state.activeMaterialTab === MaterialTabEnum.COPY">
            <!-- 唯一强分段：视频驱动文案 / 发布文案 -->
            <view class="lib-seg lib-seg--copy">
                <view
                    v-for="tab in state.copyTabs"
                    :key="tab.key"
                    class="lib-seg-item"
                    :class="state.copyTab === tab.key ? 'lib-seg-item--on' : ''"
                    @click="actions.switchCopyTab(tab.key)">
                    <text class="lib-seg-label">
                        {{ tab.label }}
                    </text>
                </view>
            </view>

            <view v-if="state.copyTab === CopyLibraryTypeEnum.DRIVE" class="copy-type-chips">
                <view
                    v-for="type in state.copyDriveTypes"
                    :key="type.key"
                    class="copy-type-chip active:opacity-70"
                    :class="state.copyDriveType === type.key ? 'copy-type-chip--active' : ''"
                    @click="actions.switchCopyDriveType(type.key)">
                    <text class="copy-type-label">{{ type.label }}</text>
                </view>
            </view>

            <!-- 空库直接提供导入；有文案后再展示管理操作 -->
            <view class="resource-heading copy-heading">
                <view class="resource-heading-copy">
                    <text class="resource-section-title">{{ currentCopyTitle }}</text>
                    <text class="resource-meta">{{ state.copyLoading ? "加载中…" : `已加载 ${state.copyList.length} 条` }}</text>
                </view>
                <view
                    class="copy-heading-actions"
                    :class="{ 'copy-content--loading': state.copyLoading }"
                    :aria-hidden="state.copyLoading">
                    <view
                        v-if="!state.copyBatchMode"
                        class="copy-toolbar-button copy-toolbar-button--outline active:opacity-70"
                        @click="actions.openPublicCopyPicker()">
                        <text class="copy-toolbar-label">从公共热门文案添加</text>
                    </view>
                    <view
                        v-if="!state.copyBatchMode && state.copyList.length"
                        class="copy-toolbar-button copy-toolbar-button--outline active:opacity-70"
                        @click="actions.addCopy()">
                        <text class="copy-toolbar-label">新增</text>
                    </view>
                    <view
                        v-if="!state.copyBatchMode"
                        class="copy-toolbar-button copy-toolbar-button--soft active:opacity-70"
                        @click="actions.aiGenerate()">
                        <text class="copy-toolbar-label">AI 生成</text>
                    </view>
                    <view
                        v-if="state.copyList.length"
                        class="copy-toolbar-button active:opacity-70"
                        @click="handleCopyManage()">
                        <text class="copy-toolbar-label">{{ state.copyBatchMode ? "管理中" : "管理" }}</text>
                    </view>
                    <view
                        v-else-if="!state.copyBatchMode"
                        class="copy-toolbar-button active:opacity-70"
                        @click="actions.importCopy()">
                        <text class="copy-toolbar-label">批量导入</text>
                    </view>
                </view>
            </view>

            <!-- 加载时保留结果区占位，避免页面高度收缩导致滚动位置跳变。 -->
            <view class="copy-results" :aria-busy="state.copyLoading">
                <view
                    v-if="state.copyList.length"
                    class="resource-list"
                    :class="{ 'copy-content--loading': state.copyLoading }"
                    :aria-hidden="state.copyLoading">
                    <view
                        v-for="item in state.copyList"
                        :key="item.id"
                        class="resource-panel border-[2rpx] border-solid"
                        :class="
                            state.copyBatchMode && state.isCopySelected(item.id) ? 'border-primary' : 'border-[transparent]'
                        "
                        @click="state.copyBatchMode ? actions.toggleCopySelected(item.id) : actions.editCopy(item)">
                        <view class="flex items-start gap-[16rpx]">
                            <view class="flex-1 min-w-0">
                                <view class="flex items-center flex-wrap gap-[8rpx]">
                                    <text class="resource-item-title break-all line-clamp-3">
                                        {{ item.title || "未填写标题" }}
                                    </text>
                                    <text v-if="isPublicCopy(item)" class="copy-badge copy-badge--public">公共</text>
                                    <text v-if="item.has_update" class="copy-badge copy-badge--upd">有更新</text>
                                    <text v-else-if="item.edited" class="copy-badge copy-badge--edit">已改过</text>
                                </view>
                                <text
                                    v-if="item.has_update"
                                    class="block mt-[6rpx] text-[22rpx] text-[#F86E21]">
                                    平台改过这条模板，点「更新」拿最新版
                                </text>
                            </view>
                            <view
                                v-if="state.copyBatchMode"
                                class="shrink-0 mt-[2rpx] w-[40rpx] h-[40rpx] rounded-full border-[4rpx] border-solid flex items-center justify-center"
                                :class="
                                    state.isCopySelected(item.id)
                                        ? 'bg-primary border-primary'
                                        : 'bg-white border-[#C8C9CC]'
                                "
                                @click.stop="actions.toggleCopySelected(item.id)">
                                <u-icon
                                    v-if="state.isCopySelected(item.id)"
                                    name="checkbox-mark"
                                    color="#FFFFFF"
                                    size="22"></u-icon>
                            </view>
                        </view>
                        <text
                            v-if="!isNewsCopy(item.library_type, item.driver_type) && item.content"
                            class="block mt-[10rpx] text-[28rpx] text-[#647184] leading-[1.55] line-clamp-3 break-all">
                            {{ item.content }}
                        </text>
                        <view
                            v-if="isPublishCopy(item.library_type) && topicTags(item.topic).length"
                            class="flex flex-wrap gap-[10rpx] mt-[10rpx]">
                            <text
                                v-for="(tag, i) in topicTags(item.topic)"
                                :key="i"
                                class="text-[24rpx] leading-[1.65] text-[#647184]">
                                {{ tag }}
                            </text>
                        </view>
                        <view
                            v-if="!isPublishCopy(item.library_type) && hasPairedPublish(item)"
                            class="mt-[12rpx] rounded-[16rpx] bg-[#F7F9FC] px-[16rpx] py-[12rpx]">
                            <text class="block text-[22rpx] text-[#8A97A8]">发布文案</text>
                            <text
                                v-if="item.publish_title"
                                class="block mt-[6rpx] text-[26rpx] text-[#334155] break-all line-clamp-2">
                                {{ item.publish_title }}
                            </text>
                            <text
                                v-if="item.publish_content"
                                class="block mt-[6rpx] text-[24rpx] text-[#647184] leading-[1.5] break-all line-clamp-2">
                                {{ item.publish_content }}
                            </text>
                            <view v-if="topicTags(item.publish_topic || '').length" class="flex flex-wrap gap-[10rpx] mt-[8rpx]">
                                <text
                                    v-for="(tag, i) in topicTags(item.publish_topic || '')"
                                    :key="i"
                                    class="text-[24rpx] leading-[1.65] text-[#647184]">
                                    {{ tag }}
                                </text>
                            </view>
                        </view>
                        <view v-if="!state.copyBatchMode" class="flex items-center gap-[20rpx] mt-[8rpx]">
                            <view class="resource-button resource-button--text active:opacity-70" @click.stop="actions.copyText(item)">
                                <text class="resource-button-label">复制</text>
                            </view>
                            <view class="resource-button resource-button--text active:opacity-70" @click.stop="actions.editCopy(item)">
                                <text class="resource-button-label">编辑</text>
                            </view>
                            <view
                                v-if="isPublicCopy(item) && item.source_alive"
                                class="resource-button resource-button--text active:opacity-70"
                                @click.stop="actions.refreshCopy(item)">
                                <text class="resource-button-label">更新</text>
                            </view>
                            <view class="resource-button resource-button--text active:opacity-70" @click.stop="actions.removeCopy(item)">
                                <text class="resource-button-label">删除</text>
                            </view>
                        </view>
                    </view>
                </view>
                <view
                    v-else
                    :class="{ 'copy-content--loading': state.copyLoading }"
                    :aria-hidden="state.copyLoading">
                    <resource-empty-state
                        icon="file-text"
                        title="先准备一条文案"
                        :description="
                            isCopyPublish
                                ? '这里存放发布到平台时使用的标题、正文和话题。'
                                : isCopyNews
                                    ? '新闻体只填写标题内容，用于驱动新闻体视频。'
                                    : '填写视频里想表达的内容，也可以粘贴已有文案。'
                        "
                        action-text="新增一条"
                        reserve-description-space
                        @action="actions.addCopy()" />
                </view>
                <view v-if="state.copyLoading" class="copy-result-loading" role="status">
                    <u-loading mode="circle" size="28" color="#647184"></u-loading>
                    <text class="resource-meta">加载中…</text>
                </view>
            </view>

            <view class="copy-help">
                <view class="flex items-center gap-[8rpx] min-h-[88rpx] active:opacity-70" @click="copyTipExpanded = !copyTipExpanded">
                    <text class="resource-meta">文案类型说明</text>
                    <u-icon :name="copyTipExpanded ? 'arrow-up' : 'arrow-down'" color="#647184" size="22"></u-icon>
                </view>
                <text v-if="copyTipExpanded" class="resource-meta block">
                    “视频驱动文案”用于生成视频。同一行可以带一份发布文案，生成时会一起用上；没带的仍走发布文案库、自定义或 AI。“发布文案”是独立库，只在这条视频没有配对时使用。
                </text>
            </view>

            <!-- 批量删除固定操作栏 -->
            <view v-if="state.copyBatchMode" class="h-[150rpx]"></view>
            <view v-if="state.copyBatchMode" class="mat-batch-bar">
                <view class="flex items-center gap-[10rpx] shrink-0" @click="actions.toggleSelectAllCopy()">
                    <view
                        class="w-[40rpx] h-[40rpx] rounded-full border-[4rpx] border-solid flex items-center justify-center"
                        :class="state.isAllCopySelected ? 'bg-primary border-primary' : 'bg-white border-[#C8C9CC]'">
                        <u-icon v-if="state.isAllCopySelected" name="checkbox-mark" color="#FFFFFF" size="24"></u-icon>
                    </view>
                    <text class="text-xs font-semibold text-[#666666]">全选</text>
                </view>
                <text class="text-xs text-[#999999]"> 已选 {{ state.selectedCopyCount }} 项 </text>
                <view class="flex items-center gap-[14rpx] ml-auto shrink-0">
                    <view
                        class="text-xs text-[#999999] bg-[#F4F6FA] border border-solid border-[#E2E2E2] rounded-full px-[30rpx] py-[14rpx]"
                        @click="actions.cancelCopyBatch()">
                        取消
                    </view>
                    <view
                        class="text-xs text-white bg-[#F56C6C] rounded-full px-[30rpx] py-[14rpx]"
                        @click="actions.confirmCopyBatchDelete()">
                        删除所选
                    </view>
                </view>
            </view>
        </template>
        <library-setting-sheet
            v-model="showLibrarySetting"
            :material-kind="state.materialKind"
            @update:material-kind="actions.switchMaterialKind" />
        <material-requirements-popup
            v-model="showMaterialRequirements"
            :material-kind="state.materialKind"
            :publish-mode="state.publishMode"
            :material-min="state.materialMin" />
        <u-popup v-model="showGroupNamePopup" mode="center" width="86%" border-radius="24">
            <view class="mat-name-popup">
                <text class="mat-name-title">{{ editingGroupId ? "修改名称" : "新建分组" }}</text>
                <input
                    v-model="groupNameDraft"
                    class="mat-name-input"
                    maxlength="30"
                    placeholder="分组名称，最多 30 个字"
                    confirm-type="done"
                    @confirm="submitGroupName" />
                <view class="mat-name-actions">
                    <view class="mat-name-btn" @click="showGroupNamePopup = false">取消</view>
                    <view class="mat-name-btn mat-name-btn--on" @click="submitGroupName">确定</view>
                </view>
            </view>
        </u-popup>
        <u-popup v-model="showUploadGroupSheet" mode="bottom" border-radius="24" :safe-area-inset-bottom="true">
            <view class="mat-move-sheet">
                <text class="mat-name-title">保存到分组</text>
                <scroll-view scroll-y class="mat-move-list">
                    <view
                        v-for="folder in state.materialFolders"
                        :key="folder.id"
                        class="mat-move-row active:opacity-70"
                        @click="chooseUploadGroup(folder.id)">
                        <text class="line-clamp-1">{{ folder.name }}</text>
                    </view>
                    <view class="mat-move-row active:opacity-70" @click="chooseUploadGroup(0)">
                        <text>未分组</text>
                    </view>
                </scroll-view>
            </view>
        </u-popup>
        <u-popup v-model="showMoveSheet" mode="bottom" border-radius="24" :safe-area-inset-bottom="true">
            <view class="mat-move-sheet">
                <text class="mat-name-title">移动至</text>
                <scroll-view scroll-y class="mat-move-list">
                    <view
                        v-for="folder in state.materialFolders"
                        :key="folder.id"
                        class="mat-move-row active:opacity-70"
                        @click="chooseMoveTarget(folder.id)">
                        <text class="line-clamp-1">{{ folder.name }}</text>
                    </view>
                    <view class="mat-move-row active:opacity-70" @click="chooseMoveTarget(0)">
                        <text>未分组</text>
                    </view>
                </scroll-view>
            </view>
        </u-popup>
    </view>
</template>

<script setup lang="ts">
import { computed, onUnmounted, ref, watch } from "vue";
import { formatAudioTime } from "@/utils/util";
import LibrarySettingSheet from "./popups/library-setting-sheet.vue";
import MaterialRequirementsPopup from "./popups/material-requirements-popup.vue";
import SliceProgress from "./slice-progress.vue";
import ResourceEmptyState from "./resource-empty-state.vue";
import { MaterialKindEnum } from "@/ai_modules/person/enums";
import {
    MaterialTabEnum,
    materialKindOptions,
    type PublishMode,
    type SliceStatistics,
} from "../hooks/useMaterialsTab";
import {
    CopyDriverTypeEnum,
    CopyLibraryTypeEnum,
    isNewsCopy,
    isPublicCopy,
    isPublishCopy,
    type CopyItem,
} from "../hooks/useCopyLibrary";

const props = defineProps<{
    squarePickerVisible?: boolean;
    publicCopyPickerVisible?: boolean;
    state: {
        activeMaterialTab: MaterialTabEnum;
        batchDeleteMode: boolean;
        publishMode: PublishMode;
        materialShortage: number | null;
        materialTabCounts: Record<string, number>;
        /** 成品直发库每条素材的用量说明，跟 AI 员工里设的随机规则走 */
        directReuseLabel: string;
        materialMin: number;
        materialPublishModes: readonly { value: PublishMode; title: string; desc: string }[];
        materialKind: MaterialKindEnum;
        materialKindOptions: readonly { value: MaterialKindEnum; label: string }[];
        materialSubTabs: readonly { key: MaterialTabEnum; label: string }[];
        materialFilters: readonly { name: string; value: string | number }[];
        activeMaterialFilter: number;
        materialList: any[];
        squareJoinedIds?: number[];
        materialLoading: boolean;
        materialFinished: boolean;
        materialFolderView: "all" | "groups";
        materialFolderId: number | null;
        materialFolderName: string;
        materialFolders: { id: number; name: string; material_count: number }[];
        ungroupedCount: number;
        showingMaterialGroupList: boolean;
        hasOverusedMaterial: boolean;
        failedMaterialCount: number;
        hasSlicingMaterial: boolean;
        hasSlicingTask: boolean;
        sliceStatistics: SliceStatistics | null;
        avatars: any[];
        voices: any[];
        selectedMaterialCount: number;
        isAllMaterialSelected: boolean;
        isMaterialSelected: (id: string) => boolean;
        isMaterialSlicing: (item: any) => boolean;
        isMaterialSliceFailed: (item: any) => boolean;
        isCurrentPlaying: (voiceId: string) => boolean;
        isCurrentMusicPlaying: (musicId: string) => boolean;
        formatMaterialTime: (time: string) => string;
        musicList: any[];
        musicLoading: boolean;
        musicFinished: boolean;
        musicBatchMode: boolean;
        selectedMusicCount: number;
        isMusicSelected: (id: string) => boolean;
        isAllMusicSelected: boolean;
        copyTab: CopyLibraryTypeEnum;
        copyDriveType: CopyDriverTypeEnum;
        copyList: CopyItem[];
        copyLoading: boolean;
        copyTabs: readonly { key: CopyLibraryTypeEnum; label: string }[];
        copyDriveTypes: readonly { key: CopyDriverTypeEnum; label: string }[];
        copyBatchMode: boolean;
        selectedCopyCount: number;
        isCopySelected: (id: number) => boolean;
        isAllCopySelected: boolean;
    };
    actions: {
        popupChange: (visible: boolean) => void;
        openSquarePicker: () => void;
        closeSquarePicker: () => void;
        openPublicCopyPicker: () => void;
        closePublicCopyPicker: () => void;
        switchMaterialTab: (tab: MaterialTabEnum) => void;
        switchPublishMode: (mode: PublishMode) => void;
        switchMaterialScope: (view: "all" | "groups") => void;
        openMaterialFolder: (folder: { id: number; name: string }) => void;
        backMaterialFolder: () => void;
        saveMaterialGroup: (name: string, id?: number) => Promise<boolean>;
        deleteMaterialGroup: (id: number) => void;
        moveMaterials: (groupId: number) => Promise<boolean>;
        loadMaterialGroups: () => Promise<void>;
        switchMaterialKind: (kind: MaterialKindEnum) => void;
        upload: () => void;
        uploadToGroup: (groupId: number) => void;
        openDirectUpload: () => void;
        uploadMusic: () => void;
        toggleMusicBatch: () => void;
        toggleMusicSelected: (id: string) => void;
        toggleSelectAllMusic: () => void;
        cancelMusicBatch: () => void;
        confirmMusicBatchDelete: () => void;
        materialFilter: (index: number) => void;
        toggleBatchDelete: () => void;
        toggleMaterialSelected: (id: string) => void;
        toggleSelectAll: () => void;
        cancelBatchDelete: () => void;
        confirmBatchDelete: () => void;
        deleteFailedSlices: () => void;
        previewMaterial: (item: any) => void;
        playMaterial: (item: any) => void;
        playMusic: (item: any) => void;
        moreMaterial: (item: any) => void;
        removeMaterial: (item: any) => Promise<void>;
        removeMusic: (item: any) => Promise<void>;
        removeAvatar: (item: any) => void;
        openVoiceForAvatar: (item: any) => void;
        addAvatar: () => void;
        playVoice: (item: any) => void;
        removeVoice: (item: any) => void;
        addVoice: () => void;
        switchCopyTab: (type: CopyLibraryTypeEnum) => void;
        switchCopyDriveType: (type: CopyDriverTypeEnum) => void;
        addCopy: () => void;
        editCopy: (item: CopyItem) => void;
        copyText: (item: CopyItem) => void;
        refreshCopy: (item: CopyItem) => void;
        removeCopy: (item: CopyItem) => void;
        importCopy: () => void;
        aiGenerate: () => void;
        toggleCopyBatch: () => void;
        toggleCopySelected: (id: number) => void;
        toggleSelectAllCopy: () => void;
        cancelCopyBatch: () => void;
        confirmCopyBatchDelete: () => void;
    };
}>();

const showLibrarySetting = ref(false);
const showGroupNamePopup = ref(false);
const groupNameDraft = ref("");
const editingGroupId = ref(0);
const showMoveSheet = ref(false);
const showUploadGroupSheet = ref(false);
const showMaterialRequirements = ref(false);
const showCopyActionSheet = ref(false);
const showMaterialActionSheet = ref(false);
const localOverlayVisible = computed(
    () =>
        Boolean(props.squarePickerVisible) ||
        Boolean(props.publicCopyPickerVisible) ||
        showLibrarySetting.value ||
        showGroupNamePopup.value ||
        showMoveSheet.value ||
        showUploadGroupSheet.value ||
        showMaterialRequirements.value ||
        showCopyActionSheet.value ||
        showMaterialActionSheet.value ||
        (props.state.activeMaterialTab === MaterialTabEnum.COMPOSE &&
            props.state.publishMode === 1 && props.state.batchDeleteMode) ||
        (props.state.activeMaterialTab === MaterialTabEnum.MUSIC && props.state.musicBatchMode) ||
        (props.state.activeMaterialTab === MaterialTabEnum.COPY && props.state.copyBatchMode),
);

// 小程序卸载时原生事件编号可能已复用，弹层状态通过现有 actions 直接同步，避免误触发底部上传。
watch(localOverlayVisible, (visible) => props.actions.popupChange(visible), { immediate: true });
onUnmounted(() => {
    props.actions.closeSquarePicker();
    props.actions.closePublicCopyPicker();
    props.actions.popupChange(false);
});

const avatarVoiceLabel = (item: { is_original_voice?: number; bind_desc?: string; voice_name?: string }): string => {
    const label = item.is_original_voice === 1
        ? item.bind_desc || "形象原音"
        : item.voice_name || "未绑定音色";
    return label.replace(/^(?:已绑(?:定)?[：:\s]*)+/, "") || "形象原音";
};

const currentKindLabel = computed(() => {
    const kind = materialKindOptions.find((item) => item.value === props.state.materialKind);
    return kind ? `${kind.label}${props.state.materialKind === MaterialKindEnum.ORIGINAL ? "模式" : ""}` : "素材模式";
});

const musicExtLabel = (url = ""): string => {
    const ext = (url || "").split("?")[0]?.split(".").pop()?.toUpperCase() || "";
    return ["MP3", "WAV"].includes(ext) ? ext : "";
};

// 文案库：新闻体仅标题；发布文案含话题标签
const isCopyNews = computed(() => isNewsCopy(props.state.copyTab, props.state.copyDriveType));
const isCopyPublish = computed(() => isPublishCopy(props.state.copyTab));
const topicTags = (topic: string): string[] => (topic ? topic.split(/\s+/).filter(Boolean) : []);
const hasPairedPublish = (item: { publish_title?: string; publish_content?: string; publish_topic?: string }): boolean =>
    Boolean(item.publish_title || item.publish_content || item.publish_topic);
const currentCopyTitle = computed(
    () => isCopyPublish.value
        ? "发布文案"
        : props.state.copyDriveTypes.find((item) => item.key === props.state.copyDriveType)?.label ?? "视频驱动文案",
);

/** 文案说明默认收起，避免与多层筛选抢首屏 */
const copyTipExpanded = ref(false);

const openGroupName = (folder?: { id: number; name: string }): void => {
    editingGroupId.value = folder?.id || 0;
    groupNameDraft.value = folder?.name || "";
    showGroupNamePopup.value = true;
};

const submitGroupName = async (): Promise<void> => {
    const ok = await props.actions.saveMaterialGroup(groupNameDraft.value, editingGroupId.value);
    if (ok) showGroupNamePopup.value = false;
};

const manageMaterialGroup = (folder: { id: number; name: string }): void => {
    showMaterialActionSheet.value = true;
    uni.showActionSheet({
        itemList: ["修改名称", "删除分组"],
        complete: () => {
            showMaterialActionSheet.value = false;
        },
        success: ({ tapIndex }) => {
            if (tapIndex === 0) {
                openGroupName(folder);
                return;
            }
            uni.showModal({
                title: "删除分组",
                content: "分组里的素材会回到未分组，文件不会删除。",
                confirmColor: "#F56C6C",
                confirmText: "删除",
                success: ({ confirm }) => {
                    if (confirm) props.actions.deleteMaterialGroup(folder.id);
                },
            });
        },
    });
};

const openMoveSheet = async (): Promise<void> => {
    if (!props.state.selectedMaterialCount) {
        uni.$u.toast("请选择要移动的素材");
        return;
    }
    await props.actions.loadMaterialGroups();
    showMoveSheet.value = true;
};

const chooseMoveTarget = async (groupId: number): Promise<void> => {
    const ok = await props.actions.moveMaterials(groupId);
    if (ok) showMoveSheet.value = false;
};

const chooseUploadGroup = (groupId: number): void => {
    showUploadGroupSheet.value = false;
    props.actions.uploadToGroup(groupId);
};

const handleMaterialActions = (): void => {
    const items: { label: string; run: () => void }[] = [];
    if (
        !props.state.batchDeleteMode &&
        props.state.publishMode === 1 &&
        props.state.materialFolderView === "all"
    ) {
        items.push({ label: "从素材库广场添加", run: () => props.actions.openSquarePicker() });
    }
    if (!props.state.batchDeleteMode) {
        items.push({
            label: "上传",
            run: () => {
                if (!props.state.showingMaterialGroupList) {
                    props.actions.upload();
                    return;
                }
                props.actions.loadMaterialGroups().finally(() => {
                    showUploadGroupSheet.value = true;
                });
            },
        });
    }
    if (
        props.state.publishMode === 1 &&
        !props.state.showingMaterialGroupList &&
        !props.state.batchDeleteMode &&
        props.state.materialList.length
    ) {
        items.push({
            label: "批量移动至分组",
            run: () => props.actions.toggleBatchDelete(),
        });
    }
    if (props.state.publishMode === 1 && !props.state.showingMaterialGroupList && props.state.materialList.length) {
        items.push({
            label: props.state.batchDeleteMode ? "退出批量删除" : "批量删除",
            run: () => props.actions.toggleBatchDelete(),
        });
    }
    if (!items.length) return;
    showMaterialActionSheet.value = true;
    uni.showActionSheet({
        itemList: items.map((item) => item.label),
        complete: () => { showMaterialActionSheet.value = false; },
        success: ({ tapIndex }) => items[tapIndex]?.run(),
    });
};

// 文案库次要操作收敛到「⋯管理」
const handleCopyManage = (): void => {
    showCopyActionSheet.value = true;
    uni.showActionSheet({
        itemList: ["批量导入", props.state.copyBatchMode ? "退出批量删除" : "批量删除"],
        complete: () => { showCopyActionSheet.value = false; },
        success: ({ tapIndex }) => {
            if (tapIndex === 0) props.actions.importCopy();
            else if (tapIndex === 1) props.actions.toggleCopyBatch();
        },
    });
};

</script>

<style scoped lang="scss">
.resource-tabs {
    @apply grid grid-cols-5 gap-[8rpx] mb-[36rpx];
}

.resource-tab {
    @apply min-w-0 min-h-[114rpx] px-[2rpx] py-[14rpx] flex flex-col items-center justify-center gap-[4rpx] rounded-[24rpx] text-[#627085] bg-[transparent] border-[2rpx] border-solid border-[transparent];
}

.resource-tab-label {
    @apply text-[24rpx] whitespace-nowrap;
}

.resource-tab-count {
    @apply text-[22rpx] text-[#68768A] tabular-nums;
}

.resource-tab--active {
    @apply bg-[#E8F1FF] text-[#075ECB] border-[#D9E8FF];

    .resource-tab-label {
        @apply font-[650];
    }

    .resource-tab-count {
        @apply text-[#075ECB];
    }
}

.resource-heading {
    @apply flex flex-wrap items-start justify-between gap-[24rpx] mb-[24rpx];
}

.resource-heading-copy {
    @apply flex-1 min-w-[280rpx] flex flex-col gap-[8rpx];
}

.resource-section-title {
    @apply text-[28rpx] leading-[1.55] font-[650] text-[#26364E];
}

.resource-meta {
    @apply text-[24rpx] leading-[1.65] text-[#647184];
}

.resource-item-title {
    @apply text-[28rpx] leading-[1.55] font-[620] text-[#26364E];
}

.resource-grid {
    @apply grid grid-cols-2 gap-[24rpx];
}

.resource-list {
    @apply flex flex-col gap-[24rpx];
}

.resource-panel {
    @apply min-w-0 bg-white rounded-[36rpx] p-[32rpx];
}

.resource-list-row {
    @apply flex items-center gap-[22rpx] py-[28rpx] border-[0] border-b-[2rpx] border-solid border-[#E7ECF2];

    &:last-child {
        @apply border-b-[0];
    }
}

.resource-button {
    @apply relative inline-flex items-center justify-center gap-[8rpx] min-h-[88rpx] min-w-[88rpx] px-[20rpx] text-[24rpx] leading-[1.4] font-medium shrink-0 whitespace-nowrap;
}

.resource-button-label {
    @apply relative;
}

.resource-button--soft::before,
.resource-button--outline::before {
    content: "";
    @apply absolute inset-x-0 top-[14rpx] bottom-[14rpx] rounded-[12rpx] border-[2rpx] border-solid border-[transparent] pointer-events-none;
}

.resource-button--soft {
    @apply text-[#075ECF];

    &::before {
        @apply bg-[#EDF4FF];
    }
}

.resource-button--outline {
    @apply text-[#36516F];

    &::before {
        @apply bg-white border-[#D8E2ED];
    }
}

.resource-button--text {
    @apply text-[#0761D9] px-[12rpx];
}

.resource-button--compact {
    @apply px-[16rpx];
}

.material-toolbar {
    @apply flex items-center justify-between my-[8rpx];
}

.material-toolbar-filters {
    @apply flex items-center min-w-0;
}

.material-filter {
    @apply relative min-h-[88rpx] min-w-[88rpx] px-[20rpx] flex items-center justify-center text-[24rpx] leading-[1.4] font-medium text-[#627085];
}

.material-filter-label {
    @apply relative whitespace-nowrap;
}

.material-filter--active {
    @apply text-[#075ECF];

    &::before {
        content: "";
        @apply absolute inset-x-0 top-[14rpx] bottom-[14rpx] rounded-[12rpx] bg-[#E8F1FF] pointer-events-none;
    }
}

.resource-action {
    @apply min-h-[88rpx] min-w-[88rpx] px-[12rpx] flex items-center justify-center gap-[8rpx] text-[24rpx] leading-[1.4] font-medium text-[#0761D9] shrink-0;
}

.avatar-panel {
    @apply min-w-0 p-[20rpx] rounded-[36rpx] bg-white;
}

.avatar-cover {
    @apply relative w-full pt-[125%] mb-[24rpx] rounded-[20rpx] bg-[#EDF2F8] overflow-hidden;
}

.avatar-bind {
    @apply w-full mt-[20rpx];
}

.avatar-bind--off {
    @apply text-[#9AA6B6];
}

.asset-mask {
    @apply absolute inset-0 flex items-center justify-center bg-[#0B172A]/45;
}

.asset-mask-label {
    @apply px-[20rpx] py-[8rpx] rounded-full bg-[#0B172A]/70 text-[22rpx] text-white font-[650];
}

.asset-unavailable {
    @apply text-[24rpx] leading-[1.65] text-[#CC7A00];
}

.avatar-remove {
    @apply text-[24rpx];
}

.avatar-add {
    @apply min-w-0 min-h-[360rpx] p-[28rpx] rounded-[28rpx] bg-white border-[2rpx] border-dashed border-[#E5EAF1] flex flex-col items-center justify-center gap-[20rpx] text-[28rpx] text-[#54739B];
}

.material-card {
    @apply relative min-w-0 rounded-[26rpx] bg-white overflow-hidden border-[2rpx] border-solid;
}

.sq-dead {
    @apply grayscale;
    .material-cover {
        @apply bg-[#E5E7EB];
    }
}

.material-cover {
    @apply relative w-full min-h-[280rpx] pt-[125%] bg-[#EAF0F7];
}

.material-card-menu {
    @apply absolute right-[10rpx] top-[8rpx] w-[66rpx] h-[66rpx] p-[12rpx] rounded-full flex items-center justify-center bg-[#FFFFFFDE];
}

.material-card-meta {
    @apply p-[20rpx] text-[24rpx] leading-[1.55];
}

.mat-empty-more {
    @apply min-h-[88rpx] min-w-[88rpx] px-[10rpx] inline-flex items-center justify-center gap-[8rpx] text-[24rpx] font-medium text-[#0761D9];
}

.mat-gap {
    @apply flex items-center justify-between gap-[24rpx] px-[24rpx] py-[20rpx] rounded-[20rpx]
        bg-[#FFF8EB] border-[2rpx] border-solid border-[#F3E5C8] text-[24rpx] text-[#936114];
}

.mat-folder-back {
    @apply flex items-center gap-[8rpx] mb-[20rpx] text-[28rpx] font-[650] text-[#0761D9];
}

.mat-group-list {
    @apply flex flex-col gap-[16rpx] mb-[20rpx];
}

.mat-group-create {
    @apply min-h-[88rpx] px-[24rpx] rounded-[20rpx] flex items-center justify-center gap-[8rpx] text-[26rpx] text-[#0761D9] bg-[#E8F1FF];
}

.mat-group-row {
    @apply min-h-[108rpx] px-[24rpx] py-[18rpx] rounded-[20rpx] flex items-center gap-[16rpx] bg-white border-[2rpx] border-solid border-[#E1E7EF];
}

.mat-group-name {
    @apply block text-[28rpx] text-[#26364E];
}

.mat-group-count {
    @apply block mt-[6rpx] text-[22rpx] text-[#748297];
}

.mat-group-manage {
    @apply shrink-0 px-[22rpx] py-[10rpx] rounded-full text-[24rpx] text-[#0761D9] bg-[#E8F1FF];
}

.mat-name-popup,
.mat-move-sheet {
    @apply px-[32rpx] pt-[36rpx] pb-[28rpx];
}

.mat-name-title {
    @apply block text-center text-[30rpx] font-[650] text-[#26364E];
}

.mat-name-input {
    @apply mt-[28rpx] h-[80rpx] px-[20rpx] rounded-[16rpx] bg-[#F4F6FA] text-[28rpx];
}

.mat-name-actions {
    @apply mt-[28rpx] flex gap-[16rpx];
}

.mat-name-btn {
    @apply flex-1 h-[80rpx] rounded-full flex items-center justify-center text-[28rpx] text-[#607087] bg-[#F4F6FA];
}

.mat-name-btn--on {
    @apply text-white bg-[#0761D9];
}

.mat-move-list {
    margin-top: 12rpx;
    max-height: 60vh;
    background: transparent;
    border: none;
}

.mat-move-row {
    display: flex;
    align-items: center;
    min-height: 88rpx;
    font-size: 30rpx;
    color: #26364e;
    background: transparent;
    border: none;
}

.lib-seg {
    @apply flex bg-[#E9EDF3] rounded-[20rpx] p-[6rpx];
}

.lib-seg-item {
    @apply flex-1 min-w-0 min-h-[76rpx] px-[14rpx] py-[14rpx] rounded-[16rpx] flex items-center justify-center text-[#607087] active:opacity-70;
}

.lib-seg-label {
    @apply text-[26rpx];
}

.lib-seg-item--on {
    @apply bg-white text-[#263B59] font-[650];
    box-shadow: 0 2rpx 8rpx #27364a0a;
}

.lib-seg--materials,
.lib-seg--copy {
    @apply relative bg-[transparent] rounded-none p-0;

    &::before {
        content: "";
        @apply absolute inset-x-0 top-[10rpx] bottom-[10rpx] rounded-[16rpx] bg-[#E9EDF3] pointer-events-none;
    }

    .lib-seg-item {
        @apply relative min-h-[88rpx] py-0 bg-[transparent] shadow-none;
    }

    .lib-seg-item--on::before {
        content: "";
        @apply absolute inset-x-[4rpx] top-[14rpx] bottom-[14rpx] rounded-[12rpx] bg-white pointer-events-none;
    }

    .lib-seg-label {
        @apply relative text-[24rpx] leading-[1.4];
    }
}

.lib-seg--copy {
    @apply mb-[8rpx];
}

.material-mode-line {
    @apply flex items-center justify-between gap-[24rpx] mb-[28rpx] text-[24rpx] text-[#69768A];
}

.lib-entry-btn {
    @apply shrink-0 min-h-[88rpx] flex items-center gap-[8rpx] pl-[16rpx] text-[#0761D9];
}

.copy-type-chips {
    @apply flex flex-wrap gap-x-[8rpx] mb-[8rpx];
}

.copy-type-chip {
    @apply relative inline-flex items-center justify-center min-h-[88rpx] px-[20rpx] text-[24rpx] leading-[1.4] font-medium text-[#627085];
}

.copy-type-chip--active {
    @apply text-[#075ECF];

    &::before {
        content: "";
        @apply absolute inset-x-0 top-[14rpx] bottom-[14rpx] rounded-[12rpx] bg-[#E8F1FF] pointer-events-none;
    }
}

.copy-type-label {
    @apply relative whitespace-nowrap;
}

.copy-heading {
    @apply items-center gap-[16rpx] mb-[16rpx];

    .resource-heading-copy {
        @apply min-w-[220rpx];
    }
}

.copy-heading-actions {
    @apply flex items-center gap-[4rpx] shrink-0;
}

.copy-toolbar-button {
    @apply relative inline-flex items-center justify-center min-h-[88rpx] min-w-[88rpx] px-[16rpx] text-[24rpx] leading-[1.4] font-medium text-[#0761D9] whitespace-nowrap;
}

.copy-toolbar-label {
    @apply relative;
}

.copy-toolbar-button--soft::before,
.copy-toolbar-button--outline::before {
    content: "";
    @apply absolute inset-x-0 top-[14rpx] bottom-[14rpx] rounded-[12rpx] border-[2rpx] border-solid border-[transparent] pointer-events-none;
}

.copy-toolbar-button--soft::before {
    @apply bg-[#EDF4FF];
}

.copy-toolbar-button--outline::before {
    @apply bg-white border-[#D8E2ED];
}

.copy-badge {
    @apply text-[20rpx] px-[10rpx] py-[4rpx] rounded-[8rpx] text-white shrink-0;
}
.copy-badge--public {
    background: #075ecf;
}
.copy-badge--upd {
    background: #f86e21;
}
.copy-badge--edit {
    background: #909399;
}

.copy-results {
    @apply relative;
}

.copy-content--loading {
    @apply invisible pointer-events-none;
}

.copy-result-loading {
    @apply absolute inset-0 flex items-center justify-center gap-[12rpx] bg-white rounded-[36rpx];
}

.copy-help {
    @apply mt-[24rpx];
}

// 批量删除固定底部操作栏：贴底、处理安全区、横向铺满
.mat-batch-bar {
    @apply fixed left-0 right-0 bottom-0 z-50 flex flex-wrap items-center gap-[16rpx] px-[24rpx] pt-[22rpx];
    padding-bottom: calc(22rpx + env(safe-area-inset-bottom));
    background: #ffffff;
    box-shadow: 0 -8rpx 24rpx rgba(0, 0, 0, 0.06);
}

.mat-slice-spinner {
    width: 40rpx;
    height: 40rpx;
    border: 5rpx solid #c9dbfb;
    border-top-color: var(--color-primary, #0065fb);
    border-radius: 50%;
    animation: matSliceSpin 0.8s linear infinite;
}

@keyframes matSliceSpin {
    to {
        transform: rotate(360deg);
    }
}

@media (prefers-reduced-motion: reduce) {
    .mat-slice-spinner {
        animation: none;
    }
}
</style>
