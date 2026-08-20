<template>
    <DefineFrameCardTemplate
        v-slot="{ label, sub, type, accentBg, accentLight, accentBorder, item, onRemove, onPreview }">
        <div
            class="flex-1 flex flex-col overflow-hidden transition-all duration-300 min-h-0 rounded-[24px]"
            :class="item ? 'border-2 border-slate-200 shadow-md' : ['border-2 border-dashed', accentBorder]">
            <div class="px-5 pt-4 pb-3 flex items-center justify-between shrink-0" :class="item ? accentBg : ''">
                <div class="flex items-center gap-2">
                    <span class="text-[14px] font-[900]" :class="item ? 'text-white' : 'text-slate-700'">
                        {{ label }}
                    </span>
                    <span class="text-[11px] font-medium" :class="item ? 'text-[#ffffff]/60' : 'text-slate-400'">
                        {{ sub }}
                    </span>
                </div>
                <button
                    v-if="item"
                    class="w-7 h-7 rounded-xl bg-[#ffffff]/20 hover:bg-red-500 text-white flex items-center justify-center transition-all duration-200"
                    @click.stop="onRemove()">
                    <Icon name="el-icon-Delete" color="#fff" :size="13" />
                </button>
            </div>

            <div
                class="flex-1 relative overflow-hidden cursor-pointer group"
                :class="item ? '' : accentLight"
                @click="item && onPreview(item)">
                <template v-if="item">
                    <img :src="item.pic" class="w-full h-full object-cover" />
                    <div
                        class="absolute inset-0 bg-[#000000]/0 group-hover:bg-[#000000]/25 transition-colors duration-300 flex items-center justify-center">
                        <div
                            class="opacity-0 group-hover:opacity-100 transition-all duration-300 scale-90 group-hover:scale-100 w-11 h-11 rounded-2xl bg-[#ffffff]/20 backdrop-blur-sm border border-[#ffffff]/30 flex items-center justify-center">
                            <Icon name="el-icon-ZoomIn" color="#fff" :size="20" />
                        </div>
                    </div>
                    <div
                        class="absolute bottom-3 left-3 px-2.5 py-1 rounded-lg backdrop-blur-md border border-[#ffffff]/20 bg-[#000000]/30">
                        <span class="text-[10px] text-white font-black">图片</span>
                    </div>
                </template>

                <div v-else class="absolute inset-0">
                    <upload
                        class="w-full h-full"
                        type="image"
                        drag
                        show-progress
                        :show-file-list="false"
                        :limit="1"
                        :image-size="IMAGE_SIZE"
                        :image-resolution="IMAGE_RESOLUTION"
                        :image-accept="IMAGE_ACCEPT"
                        @change="(e) => handleFrameAction({ type: 'upload-image', event: e }, type)">
                        <div class="flex flex-col items-center justify-center p-6 h-full gap-4">
                            <div class="flex flex-col items-center gap-3">
                                <div
                                    class="w-14 h-14 rounded-2xl flex items-center justify-center border-2 border-dashed transition-all duration-300"
                                    :class="
                                        type === 'firstFrame'
                                            ? 'border-[#8b5cf6]/20 bg-[#8b5cf6]/5 group-hover:border-[#8b5cf6]/40'
                                            : 'border-[#0065fb]/20 bg-[#0065fb]/5 group-hover:border-[#0065fb]/50'
                                    ">
                                    <Icon
                                        name="el-icon-Picture"
                                        :color="type === 'firstFrame' ? '#8b5cf6' : '#0065fb'"
                                        :size="24" />
                                </div>
                                <div class="text-center">
                                    <p class="text-[12px] font-medium text-slate-500">点击上传或拖拽图片</p>
                                    <p class="text-[10px] text-slate-300 mt-0.5">JPG · PNG · WEBP</p>
                                </div>
                            </div>

                            <ElPopover
                                trigger="click"
                                :width="220"
                                popper-class="!p-2 !rounded-[20px] border-[rgba(0,101,251,0.1)] shadow-[0_10px_40px_-10px_rgba(0,101,251,0.2)]">
                                <template #reference>
                                    <button class="relative group" @click.stop>
                                        <div
                                            class="relative flex items-center gap-2 px-6 py-2.5 rounded-[16px] text-white font-[900] text-[13px]"
                                            :class="
                                                type === 'firstFrame'
                                                    ? 'bg-[#8b5cf6] shadow-[#8b5cf6]/25'
                                                    : 'bg-[#0065fb] shadow-[#0065fb]/25'
                                            ">
                                            <div
                                                class="w-5 h-5 rounded-md bg-[#ffffff]/20 flex items-center justify-center transition-transform duration-500 group-hover:rotate-90">
                                                <Icon name="el-icon-Plus" color="#fff" :size="13" />
                                            </div>
                                            <span>上传{{ label === "首帧" ? "起点" : "终点" }}</span>
                                            <div class="w-px h-3.5 bg-[#ffffff]/30"></div>
                                            <Icon name="el-icon-ArrowDown" color="rgba(255,255,255,0.7)" :size="12" />
                                        </div>
                                    </button>
                                </template>
                                <material-menu-content
                                    type="image"
                                    :ffmpeg="false"
                                    :image-limit="1"
                                    :image-size="IMAGE_SIZE"
                                    :image-resolution="IMAGE_RESOLUTION"
                                    :image-accept="IMAGE_ACCEPT"
                                    @action="handleFrameAction($event as MaterialAction, type)" />
                            </ElPopover>
                        </div>
                    </upload>
                </div>
            </div>
        </div>
    </DefineFrameCardTemplate>

    <div class="flex gap-x-3 h-full min-w-[1000px] overflow-hidden">
        <div class="flex-1 flex flex-col gap-3 overflow-hidden">
            <div class="bg-white rounded-[20px] border border-br flex flex-col overflow-hidden">
                <header
                    class="px-8 py-5 border-b border-slate-50 flex justify-between items-center bg-[#f8fafc]/80 backdrop-blur-md">
                    <div class="flex items-center gap-3">
                        <div class="w-1.5 h-6 rounded-full bg-primary shadow-[0_0_12px_rgba(0,101,251,0.4)]"></div>
                        <h3 class="text-[18px] font-medium text-slate-800 tracking-tight">画面描述</h3>
                        <div
                            class="flex items-center px-3 py-1 rounded-full"
                            :class="formData.content.length >= MAX_DESC_LENGTH ? 'bg-red-500' : 'bg-[#0065fb]/10'">
                            <span
                                class="text-[11px] font-medium tracking-wider"
                                :class="formData.content.length >= MAX_DESC_LENGTH ? 'text-white' : 'text-primary'">
                                {{ formData.content.length }} / {{ MAX_DESC_LENGTH }}
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center bg-slate-100 p-1 rounded-xl gap-1">
                        <button
                            v-for="(tab, i) in tabs"
                            :key="i"
                            @click="activeTab = i"
                            :class="[
                                'px-5 py-2 rounded-lg text-[13px] font-black transition-all duration-200',
                                activeTab === i
                                    ? 'bg-white text-primary shadow-sm'
                                    : 'text-slate-400 hover:text-slate-600',
                            ]">
                            {{ tab }}
                        </button>
                    </div>
                </header>
                <div class="p-5">
                    <div class="bg-slate-50 rounded-[20px] border border-br p-4">
                        <ElInput
                            v-model="formData.content"
                            type="textarea"
                            :rows="4"
                            resize="none"
                            :maxlength="MAX_DESC_LENGTH"
                            placeholder="描述你想要生成的视频画面... (支持纯文本、纯素材或混合输入)"
                            class="!bg-[transparent] transparent-textarea" />
                        <div class="flex items-center justify-between mt-3">
                            <div class="flex items-center gap-2">
                                <button
                                    class="flex items-center gap-1.5 h-8 px-3 rounded-lg bg-white border border-slate-200 text-slate-500 hover:border-primary hover:text-primary transition-all text-[12px] font-medium"
                                    @click="formData.content = ''">
                                    <Icon name="el-icon-Delete" :size="12" />清除
                                </button>
                                <button
                                    class="flex items-center gap-1.5 h-8 px-3 rounded-lg bg-[#0065fb]/10 text-primary hover:bg-[#0065fb]/15 transition-all text-[12px] font-black"
                                    @click="openAiGenerateContent">
                                    <Icon name="el-icon-MagicStick" :size="12" />智能体扩写
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3 mt-3 bg-amber-50 rounded-xl px-4 py-3 border border-amber-100">
                        <span class="mt-0.5 flex-shrink-0">
                            <Icon name="el-icon-WarningFilled" color="#f59e0b" :size="16" />
                        </span>
                        <span class="text-[12px] text-amber-700 leading-relaxed">
                            请勿上传含有<span class="font-black text-amber-600">真实人脸</span
                            >的素材，包括真人照片、真人视频等，以免影响生成效果或违反平台规定。
                        </span>
                    </div>
                </div>
            </div>

            <div
                v-if="activeTab === 0"
                class="flex-1 bg-white rounded-[20px] border border-br flex flex-col overflow-hidden min-h-0">
                <div
                    class="px-6 py-4 bg-[#f8fafc]/80 border-b border-slate-50 flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-1.5 h-6 rounded-full bg-orange-400 shadow-[0_0_12px_rgba(251,146,60,0.4)]"></div>
                        <h3 class="text-[16px] font-black text-slate-700 whitespace-nowrap">参考素材</h3>
                        <div class="flex items-center bg-orange-50 px-3 py-1 rounded-full">
                            <span class="text-orange-500 text-[11px] font-medium tracking-wider">
                                {{ formData.materialList.length }} 个 · 视频总时长 {{ totalVideoDuration.toFixed(1) }}s
                                / {{ VIDEO_DURATION_LIMIT }}s
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <div
                            v-if="videoMaterials.length > 0"
                            class="flex items-center gap-2 px-3 py-1.5 rounded-xl border text-[11px] font-medium"
                            :class="durationBarClass.bg">
                            <div class="w-16 h-1.5 bg-[#e2e8f0]/60 rounded-full overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all"
                                    :class="durationBarClass.bar"
                                    :style="`width:${Math.min(
                                        (totalVideoDuration / VIDEO_DURATION_LIMIT) * 100,
                                        100,
                                    )}%`"></div>
                            </div>
                            <span :class="durationBarClass.text">
                                {{ remainVideoDuration > 0 ? `剩余 ${remainVideoDuration.toFixed(1)}s` : "已达上限" }}
                            </span>
                        </div>
                        <button
                            v-if="formData.materialList.length > 0"
                            class="flex items-center gap-1.5 h-8 px-3 rounded-lg bg-red-50 border border-red-100 text-red-500 hover:bg-red-100 transition-all text-[12px] font-medium"
                            @click="handleClearAllMaterials">
                            <Icon name="el-icon-Delete" :size="12" />清除全部
                        </button>
                    </div>
                </div>

                <div class="flex-1 flex min-h-0 overflow-hidden">
                    <div class="w-[220px] shrink-0 border-r border-slate-100 flex flex-col p-4 gap-3 bg-[#fafbfc]">
                        <div class="flex flex-col gap-1.5">
                            <ElPopover
                                ref="uploadMenuPopoverRef"
                                trigger="click"
                                placement="right-start"
                                :width="220"
                                popper-class="!p-2 !rounded-[20px] border-[rgba(0,101,251,0.1)] shadow-[0_10px_40px_-10px_rgba(0,101,251,0.2)]">
                                <template #reference>
                                    <button
                                        class="w-full flex items-center gap-2.5 px-3 h-9 rounded-xl bg-white border border-slate-100 text-slate-600 hover:border-[#0065fb]/30 hover:text-primary hover:bg-[#0065fb]/5 transition-all text-[12px] font-medium">
                                        <div
                                            class="w-6 h-6 rounded-lg bg-[#0065fb]/10 flex items-center justify-center">
                                            <Icon name="el-icon-Plus" color="#0065fb" :size="13" />
                                        </div>
                                        添加素材
                                        <Icon name="el-icon-ArrowRight" color="#94a3b8" :size="11" />
                                    </button>
                                </template>
                                <material-menu-content
                                    :image-size="IMAGE_SIZE"
                                    :image-limit="
                                        IMAGE_MATERIAL_LIMIT -
                                        formData.materialList.filter((item) => item.type === 'image').length
                                    "
                                    :image-resolution="IMAGE_RESOLUTION"
                                    :video-size="VIDEO_SIZE"
                                    :video-limit="
                                        VIDEO_MATERIAL_LIMIT -
                                        formData.materialList.filter((item) => item.type === 'video').length
                                    "
                                    :image-accept="IMAGE_ACCEPT"
                                    :video-accept="VIDEO_ACCEPT"
                                    @action="
                                        (e) => {
                                            hideUploadMenuPopover();
                                            handleMaterialAction(e as MaterialAction);
                                        }
                                    " />
                            </ElPopover>
                        </div>

                        <div class="mt-auto rounded-xl bg-[#f1f5f9]/80 px-3 py-2.5 flex flex-col gap-1.5">
                            <div class="flex items-center gap-1.5">
                                <Icon name="el-icon-InfoFilled" color="#94a3b8" :size="11" />
                                <span class="text-[10px] font-black text-slate-500 uppercase tracking-wider"
                                    >规格限制</span
                                >
                            </div>
                            <div class="flex flex-col gap-1">
                                <span class="text-[10px] text-slate-400 leading-relaxed"
                                    >图片：≤ {{ IMAGE_MATERIAL_LIMIT }} 张 · &lt; {{ IMAGE_SIZE }}MB</span
                                >
                                <span class="text-[10px] text-slate-400 leading-relaxed"
                                    >视频：≤ {{ VIDEO_MATERIAL_LIMIT }} 个 · &lt; {{ VIDEO_SIZE }}MB</span
                                >
                                <span class="text-[10px] text-slate-400 leading-relaxed"
                                    >视频：{{ VIDEO_SINGLE_MIN }}~{{ VIDEO_SINGLE_MAX }}s · 总 ≤
                                    {{ VIDEO_DURATION_LIMIT }}s</span
                                >
                                <button
                                    class="text-[10px] text-primary text-left hover:underline mt-0.5"
                                    @click="openMaterialRule">
                                    查看完整规则 →
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex-1 min-w-0 flex flex-col overflow-hidden">
                        <ElScrollbar v-if="formData.materialList.length > 0" class="flex-1">
                            <div class="grid grid-cols-3 xl:grid-cols-4 gap-3 p-4">
                                <div
                                    v-for="(item, index) in formData.materialList"
                                    :key="item.id || index"
                                    class="aspect-square rounded-[18px] relative group overflow-hidden border border-slate-100 cursor-pointer transition-all hover:scale-[1.03]"
                                    @click="previewMaterial(item)">
                                    <img :src="item.pic" class="w-full h-full object-cover" />
                                    <div
                                        v-if="item.type === 'video' && item.duration"
                                        class="absolute top-2 left-2 flex items-center gap-1 px-2 py-0.5 bg-[#000000]/60 backdrop-blur rounded-md z-10">
                                        <Icon name="el-icon-VideoPlay" color="#fff" :size="9" />
                                        <span class="text-[10px] text-white font-medium"
                                            >{{ Number(item.duration).toFixed(1) }}s</span
                                        >
                                    </div>
                                    <div
                                        class="absolute bottom-2 left-2 px-2 py-0.5 bg-[#ffffff]/20 backdrop-blur-md rounded-md text-[9px] text-white font-black border border-[#ffffff]/20 z-10">
                                        {{ item.type === "video" ? "视频" : "图片" }}
                                    </div>
                                    <button
                                        class="z-20 absolute top-2 right-2 w-7 h-7 rounded-xl bg-[#ef4444]/90 backdrop-blur text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all hover:bg-red-600"
                                        @click.stop="handleDeleteMaterial(item.id, index)">
                                        <Icon name="el-icon-Close" :size="12" />
                                    </button>
                                    <div
                                        class="absolute inset-0 bg-[#000000]/0 group-hover:bg-[#000000]/15 transition-colors"></div>
                                </div>
                            </div>
                        </ElScrollbar>

                        <div v-else class="flex-1 flex flex-col items-center justify-center gap-4 p-8">
                            <div class="relative">
                                <div
                                    class="absolute inset-0 bg-[#0065fb]/10 blur-[40px] rounded-full animate-pulse"></div>
                                <div
                                    class="relative w-20 h-20 bg-slate-50 rounded-[28px] flex items-center justify-center border border-slate-100">
                                    <Icon name="el-icon-Files" color="#cbd5e1" :size="36" />
                                </div>
                            </div>
                            <div class="text-center">
                                <p class="text-[14px] font-[1000] text-slate-400 tracking-wider">素材库空空如也</p>
                                <p class="text-[11px] text-slate-300 mt-1">从左侧上传或添加素材</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-if="activeTab === 1"
                class="flex-1 bg-white rounded-[20px] border border-br flex flex-col overflow-hidden min-h-0">
                <div class="px-6 py-4 bg-[#f8fafc]/80 border-b border-slate-50 flex items-center gap-3 shrink-0">
                    <div class="w-1.5 h-6 rounded-full bg-[#a78bfa] shadow-[0_0_10px_rgba(0,101,251,0.3)]"></div>
                    <h3 class="text-[16px] font-black text-slate-700">首尾帧过渡</h3>
                    <span class="text-[11px] text-slate-400 bg-slate-50 px-3 py-1 rounded-lg border border-slate-100">
                        首帧与尾帧均为必填，必须同时上传。
                    </span>
                </div>

                <div class="flex-1 flex items-stretch p-6 gap-4 min-h-0">
                    <FrameCardTemplate
                        label="首帧"
                        sub="起点画面(必填)"
                        accent-color="#8b5cf6"
                        accent-bg="bg-[#8b5cf6]"
                        accent-light="bg-[#f5f3ff]/40"
                        accent-border="border-[#ddd6fe]"
                        type="firstFrame"
                        :item="firstFrame"
                        @remove="firstFrame = null"
                        @preview="previewMaterial" />

                    <div class="flex flex-col items-center justify-center shrink-0 gap-3 w-[72px] select-none">
                        <div class="flex items-center gap-1">
                            <span
                                v-for="i in 3"
                                :key="i"
                                class="block w-1.5 h-1.5 rounded-full bg-[#0065fb]/40"
                                :style="`animation: kfPulse 1.6s ease-in-out ${(i - 1) * 0.28}s infinite`" />
                            <span class="ml-0.5">
                                <Icon name="el-icon-Right" color="#0065fb" :size="18" />
                            </span>
                        </div>

                        <span class="text-[11px] font-black text-slate-400 tracking-widest uppercase">过渡</span>

                        <div class="w-px h-6 bg-gradient-to-b from-slate-200 to-[transparent]"></div>

                        <div class="flex flex-col items-center gap-1">
                            <div
                                class="px-2.5 py-1 rounded-full border text-[10px] font-black whitespace-nowrap bg-gradient-to-r from-[#0065fb]/8 to-[#8b5cf6]/8 border-[#0065fb]/15 text-[#0065fb]">
                                ✦ AI 自动
                            </div>
                        </div>
                    </div>

                    <FrameCardTemplate
                        label="尾帧"
                        sub="终点画面(必填)"
                        accent-color="#0065fb"
                        accent-bg="bg-primary"
                        accent-light="bg-[#0065fb]/5"
                        accent-border="border-[#0065fb]/20"
                        type="lastFrame"
                        :item="lastFrame"
                        @remove="lastFrame = null"
                        @preview="previewMaterial" />
                </div>
            </div>
        </div>

        <div
            class="basis-[43%] bg-white flex flex-col relative flex-shrink-0 rounded-[20px] p-6 border border-br overflow-hidden">
            <header class="mb-5 flex items-center justify-between">
                <div>
                    <h2 class="text-[24px] font-medium text-slate-800 tracking-tight">生成设置</h2>
                    <div class="h-1 w-12 bg-primary rounded-full mt-2"></div>
                </div>
                <button
                    class="flex items-center gap-1.5 h-8 px-3 rounded-lg bg-[#0065fb]/10 text-primary hover:bg-[#0065fb]/15 transition-all text-[12px] font-medium"
                    @click="openMaterialRule">
                    <Icon name="el-icon-InfoFilled" :size="14" />素材规则
                </button>
            </header>

            <ElScrollbar class="flex-1 -mr-4 pr-4">
                <div class="flex flex-col gap-3">
                    <div class="px-5 py-2 rounded-2xl flex items-center gap-x-3 bg-slate-50 border border-br">
                        <div class="text-[13px] font-black text-[#64748B]">视频名称</div>
                        <div class="w-[1px] h-3 bg-[#E2E8F0]"></div>
                        <div class="flex-1">
                            <ElInput
                                v-model="formData.name"
                                class="custom-input"
                                placeholder="请输入名称"
                                maxlength="50"
                                :input-style="{
                                    textAlign: 'right',
                                    fontSize: '15px',
                                    fontWeight: '900',
                                    color: '#1E293B',
                                }"
                                clearable />
                        </div>
                    </div>

                    <section class="bg-slate-50 rounded-[20px] border border-br overflow-hidden">
                        <div class="px-4 py-3 border-b border-slate-100 bg-[#f8fafc]/80">
                            <span class="text-[13px] font-black text-slate-500 uppercase tracking-wider">参数设置</span>
                        </div>

                        <div class="px-4 py-4 border-b border-slate-100">
                            <div class="flex flex-col gap-0.5 mb-3">
                                <span class="text-[13px] font-medium text-slate-700">画面比例</span>
                                <span class="text-[10px] text-slate-400">生成视频的宽高比</span>
                            </div>
                            <div class="grid grid-cols-6 gap-2">
                                <button
                                    v-for="item in VIDEO_PROPORTIONS"
                                    :key="item.value"
                                    @click="formData.aspect_ratio = item.value"
                                    :class="[
                                        'flex flex-col items-center gap-2 py-2.5 rounded-xl border transition-all duration-200',
                                        formData.aspect_ratio === item.value
                                            ? 'border-primary bg-[#0065fb]/5 shadow-sm'
                                            : 'border-slate-200 bg-white hover:border-slate-300',
                                    ]">
                                    <div class="flex items-center justify-center h-7">
                                        <div
                                            class="rounded-[3px] border-2 transition-all"
                                            :class="
                                                formData.aspect_ratio === item.value
                                                    ? 'border-primary'
                                                    : 'border-slate-400'
                                            "
                                            :style="getRatioIconStyle(item.value)"></div>
                                    </div>
                                    <span
                                        class="text-[11px] font-black"
                                        :class="
                                            formData.aspect_ratio === item.value ? 'text-primary' : 'text-slate-500'
                                        ">
                                        {{ item.label }}
                                    </span>
                                </button>
                            </div>
                        </div>

                        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between">
                            <div class="flex flex-col gap-0.5">
                                <span class="text-[13px] font-medium text-slate-700">视频分辨率</span>
                                <span class="text-[10px] text-slate-400">输出视频的清晰度</span>
                            </div>
                            <div class="flex items-center bg-white border border-slate-200 rounded-xl p-1 gap-1">
                                <button
                                    v-for="item in VIDEO_RESOLUTIONS"
                                    :key="item.value"
                                    @click="formData.resolution = item.value"
                                    :class="[
                                        'px-3 py-1.5 rounded-lg text-xs font-black transition-all duration-200',
                                        formData.resolution === item.value
                                            ? 'bg-primary text-white shadow-sm'
                                            : 'text-slate-400 hover:text-slate-600',
                                    ]">
                                    {{ item.label }}
                                </button>
                            </div>
                        </div>

                        <div class="px-4 py-4 border-b border-slate-100">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex flex-col gap-0.5">
                                    <span class="text-[13px] font-medium text-slate-700">生成时长</span>
                                    <span class="text-[10px] text-slate-400"
                                        >{{ DURATION_MIN }} ~ {{ DURATION_MAX }} 秒</span
                                    >
                                </div>
                                <div class="flex items-center gap-1.5 px-3 py-1 rounded-lg bg-[#0065fb]/10">
                                    <Icon name="el-icon-VideoPlay" color="#0065fb" :size="12" />
                                    <span class="text-primary text-[13px] font-[1000]">{{ sliderDuration }}s</span>
                                </div>
                            </div>
                            <ElSlider
                                v-model="sliderDuration"
                                :min="DURATION_MIN"
                                :max="DURATION_MAX"
                                :step="1"
                                :show-tooltip="false" />
                            <div class="flex items-center justify-between mt-1">
                                <span class="text-[10px] text-slate-400">{{ DURATION_MIN }}s</span>
                                <span class="text-[10px] text-slate-400">{{ DURATION_MAX }}s</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between px-4 py-3">
                            <div class="flex flex-col gap-0.5">
                                <span class="text-[13px] font-medium text-slate-700">生成视频数量</span>
                                <span class="text-[10px] text-slate-400"
                                    >每次 {{ VIDEO_COUNT_MIN }} ~ {{ VIDEO_COUNT_MAX }} 个</span
                                >
                            </div>
                            <div class="flex items-center gap-2">
                                <button
                                    @click="handleVideoCount('minus')"
                                    :disabled="formData.video_count <= VIDEO_COUNT_MIN"
                                    class="w-8 h-8 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-slate-500 hover:border-primary hover:text-primary transition-all disabled:opacity-30 disabled:cursor-not-allowed">
                                    <Icon name="el-icon-Minus" :size="14" />
                                </button>
                                <input
                                    v-model.number="formData.video_count"
                                    type="number"
                                    :min="VIDEO_COUNT_MIN"
                                    :max="VIDEO_COUNT_MAX"
                                    class="w-12 h-8 text-center text-[15px] font-black text-slate-800 bg-white border border-slate-200 rounded-xl outline-none focus:border-primary focus:ring-2 focus:ring-[#0065fb]/10 transition-all [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none" />
                                <button
                                    @click="handleVideoCount('add')"
                                    :disabled="formData.video_count >= VIDEO_COUNT_MAX"
                                    class="w-8 h-8 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-slate-500 hover:border-primary hover:text-primary transition-all disabled:opacity-30 disabled:cursor-not-allowed">
                                    <Icon name="el-icon-Plus" :size="14" />
                                </button>
                            </div>
                        </div>
                    </section>
                </div>
            </ElScrollbar>

            <div class="mt-3 flex items-stretch gap-2.5 p-3 bg-slate-50 rounded-[20px] border border-br">
                <div
                    class="flex items-center gap-2.5 bg-white border border-br rounded-[14px] px-2.5 cursor-pointer hover:border-[#0065fb]/30 transition shrink-0 h-full"
                    @click="openTokensCostDialog">
                    <div
                        class="w-[30px] h-[30px] rounded-[10px] bg-gradient-to-br from-[#0065fb] to-[#0ea5e9] flex items-center justify-center shrink-0">
                        <Icon name="el-icon-StarFilled" color="#fff" :size="16" />
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <span class="text-[12px] font-medium text-slate-700 leading-none">算力消耗</span>
                        <span class="text-[10px] text-slate-400">点击查看详细计费</span>
                    </div>
                    <Icon name="el-icon-ArrowRight" color="#94a3b8" :size="13" />
                </div>

                <div class="w-px bg-slate-200 self-stretch shrink-0"></div>

                <ElButton
                    class="flex-1 !rounded-[14px] !border-0 self-stretch !h-auto"
                    type="primary"
                    size="large"
                    :loading="isSubmitting"
                    :disabled="isSubmitting"
                    @click="handleCreateVideo">
                    <template v-if="isSubmitting">
                        <span class="text-white font-[1000] text-[15px] tracking-wide">生成中...</span>
                    </template>
                    <template v-else>
                        <Icon name="el-icon-VideoCamera" color="#fff" :size="20" />
                        <span class="text-white font-[1000] text-[15px] tracking-wide ml-2">
                            生成视频（{{ formData.video_count }}个）
                        </span>
                    </template>
                </ElButton>
            </div>
        </div>
        <generate-prompt
            v-if="showGeneratePrompt"
            ref="generatePromptRef"
            :system-agent-ids="[7]"
            :max-size="MAX_DESC_LENGTH"
            :prompt-type="CreateVideoTypeEnum.STORYBOARD"
            @use-content="handleGenerateContent"
            @close="showGeneratePrompt = false" />

        <choose-history
            v-if="showChooseHistory"
            ref="chooseHistoryRef"
            :type="chooseHistoryType"
            :multiple="true"
            :limit="pickerLimit"
            @select="handleSelectHistory"
            @close="showChooseHistory = false" />

        <choose-material
            v-if="showChooseMaterial"
            ref="chooseMaterialRef"
            mode="list"
            :type="chooseMaterialType"
            :limit="pickerLimit"
            @select="handleSelectMaterial"
            @close="showChooseMaterial = false" />
        <cost-pop
            ref="costPopRef"
            v-if="showTokensCost"
            :type="MontageTypeEnum.ONE_SENTENCE_VIDEO"
            @close="showTokensCost = false" />
        <material-rule ref="materialRulePopupRef" v-if="showMaterialRule" @close="showMaterialRule = false" />
        <preview-video v-if="showVideoPreview" ref="videoPreviewPlayerRef" @close="showVideoPreview = false" />
        <ElImageViewer v-if="showImagePreview" :url-list="[imagePreviewUrl]" @close="showImagePreview = false" />
    </div>
</template>
<script setup lang="ts">
import { useTemplateRef } from "vue";
import { createReusableTemplate } from "@vueuse/core";
import dayjs from "dayjs";
import { createSoraVideo } from "@/api/digital_human";
import { useUserStore } from "@/stores/user";
import { MontageTypeEnum, CreateVideoTypeEnum, SidebarTypeEnum } from "@/pages/app/digital_human/_enums";
import GeneratePrompt from "@/pages/app/digital_human/_components/generate-prompt.vue";
import MaterialMenuContent from "@/pages/app/digital_human/_components/material-menu-content.vue";
import ChooseHistory from "@/pages/app/digital_human/_components/choose-history.vue";
import ChooseMaterial from "@/pages/app/digital_human/_components/choose-material.vue";
import CostPop from "@/pages/app/digital_human/_components/cost-pop.vue";
import MaterialRule from "./_components/material-rule.vue";
import feedback from "@/utils/feedback";
import { getValidUploadFileData } from "@/pages/app/digital_human/_hooks/useUpload";

// ─────────────────────────────────────────────
// 常量
// ─────────────────────────────────────────────
const IMAGE_SIZE = 30;
const VIDEO_SIZE = 50;
const IMAGE_MATERIAL_LIMIT = 9;
const VIDEO_MATERIAL_LIMIT = 3;
const IMAGE_RESOLUTION: any = [
    [300, 6000],
    [300, 6000],
];
const MAX_DESC_LENGTH = 500;
const DURATION_MIN = 4;
const DURATION_MAX = 15;
const VIDEO_COUNT_MIN = 1;
const VIDEO_COUNT_MAX = 5;
const VIDEO_DURATION_LIMIT = 15;
const VIDEO_DURATION_WARN = 12;
const VIDEO_SINGLE_MIN = 2;
const VIDEO_SINGLE_MAX = 15;
const IMAGE_ACCEPT = ".jpg,.jpeg,.png,.webp,.bmp,.tiff,.gif";
const VIDEO_ACCEPT = ".mp4,.mov";

const VIDEO_PROPORTIONS = [
    { label: "16:9", value: "16:9" },
    { label: "4:3", value: "4:3" },
    { label: "1:1", value: "1:1" },
    { label: "3:4", value: "3:4" },
    { label: "9:16", value: "9:16" },
    { label: "21:9", value: "21:9" },
] as const;

const VIDEO_RESOLUTIONS = [
    { label: "480p", value: "480p" },
    { label: "720p", value: "720p" },
] as const;

/** 校验提示语集中管理，便于 i18n 替换 */
const VALIDATION_MESSAGES = {
    noName: "请输入视频名称",
    noContentOrMaterial: "请输入提示词或上传素材",
    contentTooLong: `提示词长度不能超过 ${MAX_DESC_LENGTH} 字`,
    noBothFrames: "请同时上传首帧和尾帧",
    videoCountRange: `视频数量请在 ${VIDEO_COUNT_MIN}~${VIDEO_COUNT_MAX} 之间`,
    durationExceeded: `视频总时长超出 ${VIDEO_DURATION_LIMIT}s 限制`,
    imageLimitReached: `图片已达上限 ${IMAGE_MATERIAL_LIMIT} 张`,
    videoLimitReached: `视频已达上限 ${VIDEO_MATERIAL_LIMIT} 个`,
    videoDurationRange: `视频时长需在 ${VIDEO_SINGLE_MIN}~${VIDEO_SINGLE_MAX}s 之间`,
    videoDurationExceeded: (remain: string) => `总时长不能超过 ${VIDEO_DURATION_LIMIT}s，当前剩余 ${remain}s`,
} as const;

const [DefineFrameCardTemplate, FrameCardTemplate] = createReusableTemplate<
    {
        label: string;
        type: "firstFrame" | "lastFrame";
        sub: string;
        accentColor: string;
        accentBg: string;
        accentLight: string;
        accentBorder: string;
        item: any;
        onRemove: () => void;
        onPreview: (item: any) => void;
    },
    { upload: undefined; menu: undefined }
>();

// ─────────────────────────────────────────────
// Store
// ─────────────────────────────────────────────
const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

// ─────────────────────────────────────────────
// 表单状态
// ─────────────────────────────────────────────
const tabs = ["参考生成", "首尾帧过渡"];
const activeTab = ref(0);
const sliderDuration = ref(7);
const isSubmitting = ref(false);
const firstFrame = ref<any>(null);
const lastFrame = ref<any>(null);

const formData = reactive({
    name: dayjs().format("YYYYMMDDHHmm") + "一句话生成视频",
    content: "",
    materialList: [] as any[],
    aspect_ratio: "16:9" as string,
    resolution: "480p" as string,
    video_count: 1,
});

// ─────────────────────────────────────────────
// 弹窗显示状态
// ─────────────────────────────────────────────
const showGeneratePrompt = ref(false);
const showMaterialRule = ref(false);
const showTokensCost = ref(false);
const showVideoPreview = ref(false);
const showImagePreview = ref(false);
const showChooseMaterial = ref(false);
const showChooseHistory = ref(false);
const imagePreviewUrl = ref("");

// ─────────────────────────────────────────────
// Refs
// ─────────────────────────────────────────────
const generatePromptRef = shallowRef<InstanceType<typeof GeneratePrompt>>();
const uploadMenuPopoverRef = useTemplateRef<any>("uploadMenuPopoverRef");
const materialRulePopupRef = shallowRef<InstanceType<typeof MaterialRule>>();
const chooseHistoryRef = shallowRef<InstanceType<typeof ChooseHistory>>();
const chooseMaterialRef = shallowRef<InstanceType<typeof ChooseMaterial>>();
const videoPreviewPlayerRef = shallowRef();
const costPopRef = shallowRef<InstanceType<typeof CostPop>>();

const hideUploadMenuPopover = () => uploadMenuPopoverRef.value?.hide?.();

// ─────────────────────────────────────────────
// ① 工具：统一「show flag → nextTick → open()」模式
// ─────────────────────────────────────────────
/**
 * 打开一个需要先设 showFlag 再 nextTick 再调 open() 的弹窗
 * @example openDialog(showGeneratePrompt, generatePromptRef)
 */
const openDialog = async (showFlag: Ref<boolean>, ref: Ref<{ open: () => void } | undefined>) => {
    showFlag.value = true;
    await nextTick();
    ref.value?.open();
};

// ─────────────────────────────────────────────
// ② Computed：素材分类 & 时长统计
// ─────────────────────────────────────────────
const videoMaterials = computed(() => formData.materialList.filter((i: any) => i.type === "video"));
const imageMaterials = computed(() => formData.materialList.filter((i: any) => i.type !== "video"));
const totalVideoDuration = computed(() =>
    videoMaterials.value.reduce((sum: number, i: any) => sum + (Number(i.duration) || 0), 0),
);
const remainVideoDuration = computed(() => Math.max(VIDEO_DURATION_LIMIT - totalVideoDuration.value, 0));

const durationBarClass = computed(() => {
    const t = totalVideoDuration.value;
    if (t > VIDEO_DURATION_LIMIT) return { bg: "bg-red-50 border-red-200", text: "text-red-500", bar: "bg-red-500" };
    if (t >= VIDEO_DURATION_WARN)
        return { bg: "bg-amber-50 border-amber-200", text: "text-amber-500", bar: "bg-amber-500" };
    return { bg: "bg-[#0065fb]/5 border-[#0065fb]/20", text: "text-primary", bar: "bg-primary" };
});

// ─────────────────────────────────────────────
// ③ 素材选择器状态（合并两个 limit computed 为一个）
// ─────────────────────────────────────────────
type UploadTarget = "material" | "firstFrame" | "lastFrame";
const uploadTarget = ref<UploadTarget>("material");
const chooseMaterialType = ref<"image" | "video" | "all">("all");
const chooseHistoryType = ref<"image" | "video" | "all">("all");

/**
 * 原先 chooseMaterialLimit / chooseHistoryLimit 逻辑完全相同，合并为一个。
 * frame 目标固定为 1；否则按类型取对应上限的剩余量。
 */
const pickerLimit = computed(() => {
    if (uploadTarget.value !== "material") return 1;
    const imageRemain = Math.max(IMAGE_MATERIAL_LIMIT - imageMaterials.value.length, 0);
    const videoRemain = Math.max(VIDEO_MATERIAL_LIMIT - videoMaterials.value.length, 0);
    const type = chooseMaterialType.value; // chooseHistoryType 与之同步赋值
    if (type === "image") return imageRemain;
    if (type === "video") return videoRemain;
    return Math.max(imageRemain, videoRemain);
});

// ─────────────────────────────────────────────
// ④ 工具函数
// ─────────────────────────────────────────────
const getRatioIconStyle = (ratio: string) => {
    const [w, h] = ratio.split(":").map(Number);
    const max = 22;
    const iw = w >= h ? max : Math.round((w / h) * max);
    const ih = w >= h ? Math.round((h / w) * max) : max;
    return `width:${iw}px;height:${ih}px;`;
};

const normalizeMaterial = (data: any, defaultType: "image" | "video"): any => ({
    id: data.id || `${Date.now()}_${Math.random()}`,
    url: data.uri || data.url,
    pic: data.pic || data.thumbnail_path || data.uri,
    type: defaultType,
    duration: data.duration ? Number(data.duration) : undefined,
});

// ─────────────────────────────────────────────
// ⑤ 素材限制守卫
// ─────────────────────────────────────────────
/**
 * 检查能否继续添加某类素材，不满足时自动提示并返回 false
 */
const canAddMaterial = (type: "image" | "video", duration?: number): boolean => {
    if (type === "image") {
        if (imageMaterials.value.length >= IMAGE_MATERIAL_LIMIT) {
            feedback.msgWarning(VALIDATION_MESSAGES.imageLimitReached);
            return false;
        }
        return true;
    }
    // video
    if (videoMaterials.value.length >= VIDEO_MATERIAL_LIMIT) {
        feedback.msgWarning(VALIDATION_MESSAGES.videoLimitReached);
        return false;
    }
    if (duration !== undefined) {
        if (duration < VIDEO_SINGLE_MIN || duration > VIDEO_SINGLE_MAX) {
            feedback.msgWarning(VALIDATION_MESSAGES.videoDurationRange);
            return false;
        }
        if (totalVideoDuration.value + duration > VIDEO_DURATION_LIMIT) {
            feedback.msgWarning(VALIDATION_MESSAGES.videoDurationExceeded(remainVideoDuration.value.toFixed(1)));
            return false;
        }
    }
    return true;
};

// ─────────────────────────────────────────────
// ⑥ 素材添加核心逻辑（复用于 material 和 frame）
// ─────────────────────────────────────────────
const appendMaterial = (item: any) => {
    const m = normalizeMaterial(item, item.type || "image");
    if (!canAddMaterial(m.type, m.duration)) return;
    formData.materialList.push(m);
};

const setFrame = (target: UploadTarget, item: any) => {
    if (target === "firstFrame") firstFrame.value = item;
    if (target === "lastFrame") lastFrame.value = item;
};

// ─────────────────────────────────────────────
// ⑦ 统一弹出素材/历史选择器
// ─────────────────────────────────────────────
const openMaterialPicker = async (type: "image" | "video" | "all", target: UploadTarget = "material") => {
    uploadTarget.value = target;
    chooseMaterialType.value = type;
    chooseHistoryType.value = type;
    await openDialog(showChooseMaterial, chooseMaterialRef);
};

const openHistoryPicker = async (type: "image" | "video" | "all", target: UploadTarget = "material") => {
    uploadTarget.value = target;
    chooseHistoryType.value = type;
    chooseMaterialType.value = type;
    await openDialog(showChooseHistory, chooseHistoryRef);
};

const openMaterialRule = () => openDialog(showMaterialRule, materialRulePopupRef);

// ─────────────────────────────────────────────
// ⑧ Material Action Handler（重构后消除约 60 行重复）
// ─────────────────────────────────────────────
type MaterialAction =
    | { type: "upload-image" | "upload-video"; event: { response: any } }
    | { type: "library-image" | "library-video" | "history" };

/**
 * 处理参考素材区的操作（target 固定为 "material"）
 */
const handleMaterialAction = async (action: MaterialAction) => {
    uploadTarget.value = "material";
    hideUploadMenuPopover();

    if (action.type === "upload-image") {
        const data = getValidUploadFileData(action.event);
        if (!data || !canAddMaterial("image")) return;
        formData.materialList.push(normalizeMaterial(data, "image"));
        return;
    }
    if (action.type === "upload-video") {
        const data = getValidUploadFileData(action.event);
        if (!data) return;
        const duration = Number(data.duration) || 0;
        if (!canAddMaterial("video", duration)) return;
        formData.materialList.push(normalizeMaterial(data, "video"));
        return;
    }
    if (action.type === "library-image") {
        if (!canAddMaterial("image")) return;
        await openMaterialPicker("image");
        return;
    }
    if (action.type === "library-video") {
        if (!canAddMaterial("video")) return;
        await openMaterialPicker("video");
        return;
    }
    if (action.type === "history") {
        await openHistoryPicker("all");
    }
};

/**
 * 处理首尾帧区的操作（target 为 "firstFrame" | "lastFrame"）
 * 首尾帧只支持图片，
 */
const handleFrameAction = async (action: MaterialAction, target: "firstFrame" | "lastFrame") => {
    uploadTarget.value = target;

    if (action.type === "upload-image") {
        const data = getValidUploadFileData(action.event);
        if (data) setFrame(target, normalizeMaterial(data, "image"));
        return;
    }
    if (action.type === "library-image") {
        await openMaterialPicker("image", target);
        return;
    }
    if (action.type === "history") {
        await openHistoryPicker("image", target);
    }
};

// ─────────────────────────────────────────────
// ⑨ 选择回调（合并 handleSelectHistory → handleSelectMaterial）
// ─────────────────────────────────────────────
const handleSelectMaterial = (list: any[]) => {
    if (uploadTarget.value !== "material") {
        setFrame(uploadTarget.value, list[0]);
        return;
    }
    list.forEach((item) => appendMaterial(item));
};

const handleSelectHistory = handleSelectMaterial;

// ─────────────────────────────────────────────
// ⑩ 其余业务逻辑（
// ─────────────────────────────────────────────
const handleVideoCount = (type: "minus" | "add") => {
    if (type === "minus") {
        if (formData.video_count <= VIDEO_COUNT_MIN) return feedback.msgWarning(`视频数量最少 ${VIDEO_COUNT_MIN}`);
        formData.video_count--;
    } else {
        if (formData.video_count >= VIDEO_COUNT_MAX) return feedback.msgWarning(`视频数量最多 ${VIDEO_COUNT_MAX}`);
        formData.video_count++;
    }
};

const openAiGenerateContent = () => openDialog(showGeneratePrompt, generatePromptRef);
const openTokensCostDialog = () => openDialog(showTokensCost, costPopRef);

const handleGenerateContent = (res: any) => {
    formData.content = Array.isArray(res)
        ? res
              .map((item) => (typeof item === "string" ? item : item?.content ?? ""))
              .filter(Boolean)
              .join("\n")
        : res;
};

const handleClearAllMaterials = () => {
    useNuxtApp().$confirm({
        title: "确认清除",
        message: "确定要清除全部已上传素材吗？",
        onConfirm: () => {
            formData.materialList = [];
        },
    });
};

const handleDeleteMaterial = (id: any, index: number) => {
    if (id) formData.materialList = formData.materialList.filter((i: any) => i.id !== id);
    else formData.materialList.splice(index, 1);
};

const previewMaterial = async (item: any) => {
    if (item.type === "video") {
        showVideoPreview.value = true;
        await nextTick();
        videoPreviewPlayerRef.value?.open();
        videoPreviewPlayerRef.value?.setUrl(item.url);
    } else {
        imagePreviewUrl.value = item.pic || item.url;
        showImagePreview.value = true;
    }
};

// ─────────────────────────────────────────────
// ⑪ 提交前校验
// ─────────────────────────────────────────────
const validateBeforeCreate = (): boolean => {
    const warn = (msg: string) => {
        feedback.msgWarning(msg);
        return false;
    };

    if (!formData.name) return warn(VALIDATION_MESSAGES.noName);

    if (activeTab.value === 0) {
        const hasContent = formData.content.trim().length > 0;
        const hasMaterial = formData.materialList.length > 0;
        if (!hasContent && !hasMaterial) return warn(VALIDATION_MESSAGES.noContentOrMaterial);
        if (formData.content.length >= MAX_DESC_LENGTH) return warn(VALIDATION_MESSAGES.contentTooLong);
    } else {
        if (!firstFrame.value || !lastFrame.value) return warn(VALIDATION_MESSAGES.noBothFrames);
    }

    if (formData.video_count < VIDEO_COUNT_MIN || formData.video_count > VIDEO_COUNT_MAX)
        return warn(VALIDATION_MESSAGES.videoCountRange);

    if (totalVideoDuration.value > VIDEO_DURATION_LIMIT) return warn(VALIDATION_MESSAGES.durationExceeded);

    return true;
};

// ─────────────────────────────────────────────
// ⑫ 提交
// ─────────────────────────────────────────────
const handleCreateVideo = async () => {
    if (userTokens.value <= 0) {
        feedback.msgPowerInsufficient();
        return;
    }
    if (!validateBeforeCreate()) return;

    isSubmitting.value = true;
    try {
        const isFrameMode = activeTab.value === 1;
        const imageUrls = isFrameMode
            ? [firstFrame.value?.url, lastFrame.value?.url].filter(Boolean)
            : imageMaterials.value.map((i: any) => i.url).slice(0, 9);

        await createSoraVideo({
            name: formData.name,
            content: formData.content,
            image_urls: imageUrls,
            video_urls: videoMaterials.value
                .map((i: any) => ({ url: i.url, duration: Number(i.duration) }))
                .slice(0, 3),
            aspect_ratio: formData.aspect_ratio,
            resolution: formData.resolution,
            duration: sliderDuration.value,
            number: formData.video_count,
            model: "seedance2.0",
            first_last_frame: isFrameMode ? 1 : 0,
        });
        handleCreateSuccess();
    } catch (error: any) {
        feedback.msgError(typeof error === "string" ? error : error?.message || "提交失败");
    } finally {
        isSubmitting.value = false;
    }
};

const handleCreateSuccess = () => {
    useNuxtApp().$confirm({
        title: "任务已提交",
        message: "创建成功，请在历史记录查看",
        confirmButtonText: "前往查看",
        cancelButtonText: "取消",
        onConfirm: () => navigateTo(`/app/digital_human?type=${SidebarTypeEnum.MY_WORKS}`),
        onCancel: () => window.location.reload(),
    });
};
</script>
<style scoped lang="scss">
:deep(.el-upload),
:deep(.el-upload-dragger) {
    width: 100%;
    height: 100%;
    border: none !important;
    background: transparent !important;
    padding: 0 !important;
    border-radius: 16px !important;
}

:deep(.upload-wrap) {
    @apply h-full;
}

:deep(.el-upload-dragger:hover) {
    background: transparent !important;
}

:deep(.frame-card-root) {
    @apply flex-1 flex flex-col rounded-[28px] border-2 overflow-hidden transition-all duration-300;
}
:deep(.frame-card-root.has-item) {
    @apply border-slate-200;
}
:deep(.frame-card-root.no-item) {
    @apply border-dashed;
}

:deep(.transparent-textarea .el-textarea__inner) {
    background: transparent !important;
    border: none !important;
    box-shadow: none !important;
    padding: 0 !important;
    font-size: 14px;
    line-height: 1.6;
    color: #1e293b;
    resize: none;
}
:deep(.transparent-textarea .el-textarea__inner::placeholder) {
    color: #c0c4cc;
    font-size: 13px;
}
:deep(.custom-input .el-input__wrapper) {
    background: transparent !important;
    box-shadow: none !important;
    padding: 0;
}
:deep(.rule-dialog .el-dialog) {
    border-radius: 24px;
    overflow: hidden;
}
:deep(.rule-dialog .el-dialog__header) {
    padding: 20px 24px;
    margin: 0;
    border-bottom: 1px solid #f1f5f9;
}
:deep(.rule-dialog .el-dialog__title) {
    font-weight: 900;
    font-size: 16px;
    color: #0f172a;
}
:deep(.rule-dialog .el-dialog__body) {
    padding: 20px 24px;
}
:deep(.el-slider__runway) {
    background-color: #e2e8f0;
}

@keyframes framePulse {
    0%,
    100% {
        opacity: 0.25;
        transform: scale(0.75);
    }
    50% {
        opacity: 1;
        transform: scale(1.15);
    }
}
</style>
