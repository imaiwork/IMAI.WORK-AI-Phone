<template>
    <div class="flex gap-x-3 h-full min-w-[1000px] overflow-hidden">
        <div class="flex-1 flex flex-col gap-3 overflow-hidden">
            <div class="flex-[1.2] bg-white rounded-[20px] border border-br flex flex-col overflow-hidden">
                <header
                    class="px-8 py-5 border-b border-slate-50 flex justify-between items-center bg-[#f8fafc]/80 backdrop-blur-md">
                    <div class="flex items-center gap-3">
                        <div class="w-1.5 h-6 rounded-full bg-primary shadow-[0_0_12px_rgba(0,101,251,0.4)]"></div>
                        <h3 class="text-[18px] font-medium text-slate-800 tracking-tight">形象素材选择</h3>
                        <div class="flex items-center bg-[#0065fb]/10 px-3 py-1 rounded-full">
                            <span class="text-primary text-[11px] font-medium uppercase tracking-wider"
                                >已选: {{ formData.anchorLists.length }}</span
                            >
                        </div>
                    </div>
                </header>
                <div class="flex-1 min-h-0">
                    <ElScrollbar :distance="20" @end-reached="loadMoreAnchor">
                        <div class="grid grid-cols-4 xl:grid-cols-5 gap-3 p-4">
                            <div
                                class="aspect-[3/4] rounded-[24px] border-2 border-dashed border-slate-200 bg-[#f8fafc]/50 hover:border-primary hover:bg-[#0065fb]/5 transition-all cursor-pointer flex flex-col items-center justify-center gap-3 group"
                                @click="toCloneAnchor()">
                                <div
                                    class="w-12 h-12 rounded-2xl bg-white shadow-light flex items-center justify-center group-hover:scale-110 group-hover:bg-primary group-hover:text-white transition-all">
                                    <Icon name="el-icon-Plus" :size="24" />
                                </div>
                                <span class="text-[13px] font-black text-slate-500 group-hover:text-primary"
                                    >形象克隆</span
                                >
                            </div>
                            <div
                                v-for="(item, index) in anchorPager.lists"
                                :key="item.id"
                                @click="toggleSelectAnchor(item)"
                                :class="[
                                    'aspect-[3/4] rounded-[24px] relative group overflow-hidden transition-all duration-300 cursor-pointer border-2',
                                    isAnchorSelected(item.id) ? 'border-primary scale-[0.98]' : 'border-[transparent]',
                                ]">
                                <ElImage :src="item.pic" fit="cover" lazy class="w-full h-full" />
                                <div
                                    v-if="isAnchorSelected(item.id)"
                                    class="absolute top-3 right-3 w-7 h-7 bg-primary rounded-full flex items-center justify-center border-2 border-white z-20 animate-in zoom-in duration-300">
                                    <Icon name="el-icon-Check" color="#fff" :size="16" />
                                </div>
                                <div
                                    class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center z-10">
                                    <div class="w-8 h-8" @click.stop="handleVideoPlay(item.anchor_url)">
                                        <play-btn :icon-size="24" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <load-text :is-load="anchorPager.isLoad" v-if="anchorPager.lists.length > 0" />
                    </ElScrollbar>
                </div>
            </div>

            <div class="flex-1 bg-white rounded-[20px] border border-br flex flex-col overflow-hidden">
                <div class="px-8 py-5 flex justify-between items-center bg-[#f8fafc]/80">
                    <div class="flex items-center gap-3">
                        <div class="w-1.5 h-6 rounded-full bg-orange-400 shadow-[0_0_12px_rgba(251,146,60,0.4)]"></div>
                        <h3 class="text-[16px] font-black text-slate-700">参考素材</h3>
                        <div
                            class="text-[11px] text-slate-400 font-medium bg-slate-50 px-3 py-1 rounded-lg border border-slate-100">
                            总量限制：全部素材总时长不得超过{{ montageUploadConfig.materialTotalDuration }}分钟
                            (图片按{{ montageUploadConfig.imageDuration }}秒/张，视频按实际时长/个)
                        </div>
                    </div>
                    <div v-if="formData.materialList.length > 0" class="flex flex-col items-end gap-2">
                        <ElPopover
                            v-if="calcTotalDuration() < montageUploadConfig.materialTotalDuration * 60"
                            ref="supplementPopoverRef"
                            trigger="click"
                            :width="260"
                            popper-class="!p-2 !rounded-[20px] border-[rgba(0,101,251,0.1)] shadow-[0_10px_40px_-10px_rgba(0,101,251,0.2)]">
                            <template #reference>
                                <ElButton link type="primary" class="!font-medium !text-[13px]">+ 补充素材</ElButton>
                            </template>
                            <material-menu-content @action="handleMaterialAction" />
                        </ElPopover>
                        <material-duration-bar
                            :used="calcTotalDuration()"
                            :max="montageUploadConfig.materialTotalDuration * 60" />
                    </div>
                </div>

                <div class="flex-1 min-h-0">
                    <ElScrollbar v-if="formData.materialList.length > 0">
                        <div class="grid grid-cols-4 xl:grid-cols-5 gap-3 p-4">
                            <div
                                v-for="(item, index) in formData.materialList"
                                :key="index"
                                class="aspect-square shrink-0 rounded-[24px] relative group overflow-hidden border border-slate-100 transition-transform hover:scale-105">
                                <img :src="item.pic" class="w-full h-full object-cover" />
                                <div
                                    class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center z-10">
                                    <div class="w-8 h-8" @click.stop="previewMaterial(item)">
                                        <play-btn :icon-size="24" />
                                    </div>
                                </div>
                                <div
                                    class="absolute inset-0 bg-[#000000]/20 group-hover:bg-[#000000]/40 transition-colors"></div>
                                <button
                                    @click.stop="handleDeleteMaterial(index)"
                                    class="z-[777] absolute top-2 right-2 w-7 h-7 rounded-xl bg-[#ef4444]/90 backdrop-blur text-white flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all hover:bg-red-600">
                                    <Icon name="el-icon-Close" :size="12" />
                                </button>
                                <div
                                    class="absolute bottom-2 left-2 px-2 py-1 bg-[#ffffff]/20 backdrop-blur-md rounded-lg text-[9px] text-white font-black border border-[#ffffff]/20">
                                    {{ item.type === "image" ? "IMAGE" : "VIDEO" }}
                                </div>
                            </div>
                        </div>
                    </ElScrollbar>

                    <div
                        class="flex flex-col justify-center items-center h-full py-12"
                        v-show="formData.materialList.length === 0">
                        <div class="relative mb-6">
                            <div class="absolute inset-0 bg-[#0065fb]/10 blur-[60px] rounded-full animate-pulse"></div>
                            <div
                                class="relative w-24 h-24 bg-slate-50 rounded-[32px] flex items-center justify-center border border-slate-100 shadow-sm">
                                <Icon name="el-icon-Files" color="var(--slate-300)" :size="40" />
                            </div>
                        </div>
                        <div class="text-[15px] font-[1000] text-slate-400 mb-8 tracking-wider uppercase">
                            当前素材库空空如也
                        </div>
                        <ElPopover
                            ref="addMaterialPopoverRef"
                            placement="top"
                            :width="260"
                            popper-class="!p-2 !rounded-[20px] border-[rgba(0,101,251,0.1)] shadow-[0_10px_40px_-10px_rgba(0,101,251,0.2)]">
                            <template #reference>
                                <button class="add-material-btn group">
                                    <div
                                        class="absolute inset-0 bg-primary opacity-0 group-hover:opacity-100 transition-opacity duration-500 blur-xl"></div>
                                    <div
                                        class="relative flex items-center gap-3 px-8 py-4 bg-primary rounded-[22px] shadow-lg shadow-[#0065fb]/30 group-hover:scale-105 group-hover:shadow-[#0065fb]/50 transition-all duration-300 active:scale-95">
                                        <div
                                            class="w-6 h-6 rounded-lg bg-[#ffffff]/20 flex items-center justify-center group-hover:rotate-90 transition-transform duration-500">
                                            <Icon name="el-icon-Plus" color="#ffffff" :size="18" />
                                        </div>
                                        <span class="text-white font-[1000] text-base tracking-wide mr-1"
                                            >添加参考素材</span
                                        >
                                        <Icon name="el-icon-ArrowDown" color="rgba(255,255,255,0.6)" :size="14" />
                                    </div>
                                    <div
                                        class="absolute -inset-1 border-2 border-[#0065fb]/30 rounded-[24px] animate-ping opacity-20 group-hover:hidden"></div>
                                </button>
                            </template>
                            <material-menu-content @action="handleMaterialAction" />
                        </ElPopover>
                    </div>
                </div>
            </div>
        </div>

        <div class="w-[450px] bg-white flex flex-col relative flex-shrink-0 rounded-[20px] p-6 border border-br">
            <header class="mb-5">
                <h2 class="text-[24px] font-medium text-slate-800 tracking-tight">生成设置</h2>
                <div class="h-1 w-12 bg-primary rounded-full mt-2"></div>
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
                                maxlength="20"
                                :input-style="{
                                    textAlign: 'right',
                                    fontSize: '15px',
                                    fontWeight: '900',
                                    color: '#1E293B',
                                }"
                                clearable />
                        </div>
                    </div>

                    <section class="bg-slate-50 rounded-[20px] p-3 border border-br">
                        <div class="flex justify-between items-center mb-4 px-2">
                            <h4 class="text-[14px] font-medium text-slate-700">人设设定</h4>
                            <button
                                @click="openChooseCharacter"
                                class="text-primary text-xs font-black hover:underline">
                                历史人设
                            </button>
                        </div>
                        <div class="space-y-4">
                            <ElInput
                                v-model="formData.person_name"
                                placeholder="人物名称 (如: 资深分析师)"
                                class="custom-input !h-11" />
                            <ElInput
                                v-model="formData.person_introduction"
                                type="textarea"
                                :rows="3"
                                placeholder="简述人物背景及..."
                                class="custom-textarea"
                                resize="none" />
                        </div>
                    </section>

                    <section class="bg-slate-50 rounded-[24px] border border-br overflow-hidden flex flex-col">
                        <div
                            class="px-6 py-4 border-b border-slate-50 bg-[#f8fafc]/50 flex items-center justify-between">
                            <h3 class="text-sm font-[1000] text-slate-800 flex items-center gap-2">
                                口播内容配置
                                <span
                                    class="px-2 py-0.5 rounded-full bg-slate-200 text-slate-500 text-[10px] font-black"
                                    >{{ formData.copywriting.length }}</span
                                >
                            </h3>
                            <div class="flex items-center gap-2">
                                <button
                                    @click="handleAddManual"
                                    class="h-9 px-4 rounded-xl border border-br text-slate-500 text-xs font-black hover:border-[#0065fb]/30 hover:text-primary transition-all flex items-center gap-2 bg-white">
                                    <Icon name="el-icon-Plus" /> 手动添加
                                </button>
                                <button
                                    @click="openAiGenerateContent"
                                    class="h-9 px-4 bg-primary text-white rounded-xl text-xs font-[500] flex items-center gap-2 shadow-light shadow-[#0065fb]/20 hover:scale-105 transition-all">
                                    <Icon name="el-icon-MagicStick" /> AI 生成
                                </button>
                            </div>
                        </div>

                        <div class="flex max-h-[420px]">
                            <div class="w-48 border-r border-slate-50 flex flex-col bg-[#f8fafc]/30">
                                <div
                                    ref="copywritingListRef"
                                    class="grow overflow-y-auto p-2 space-y-1 custom-scrollbar">
                                    <div
                                        v-for="(item, index) in formData.copywriting"
                                        :key="index"
                                        :data-copywriting-index="index"
                                        :class="[
                                            'group px-3 py-3 rounded-xl cursor-pointer transition-all relative',
                                            currentActiveIndex === index
                                                ? 'bg-white shadow-sm ring-1 ring-slate-100'
                                                : 'hover:bg-[#ffffff]/50',
                                            copywritingErrors[index] ? 'ring-1 ring-red-400 bg-red-50/50' : '',
                                        ]"
                                        @click="currentActiveIndex = index">
                                        <div
                                            v-if="currentActiveIndex === index"
                                            class="absolute left-0 top-3 bottom-3 w-1 bg-primary rounded-full"></div>
                                        <div
                                            v-if="copywritingErrors[index]"
                                            class="absolute right-7 top-2 w-2 h-2 rounded-full bg-red-400"></div>
                                        <div class="flex flex-col gap-1">
                                            <span
                                                :class="[
                                                    'text-[11px] font-black uppercase',
                                                    copywritingErrors[index]
                                                        ? 'text-red-400'
                                                        : currentActiveIndex === index
                                                        ? 'text-primary'
                                                        : 'text-slate-400',
                                                ]">
                                                #{{ (index + 1).toString().padStart(2, "0") }}
                                            </span>
                                            <span class="text-xs font-medium text-slate-600 truncate w-full">{{
                                                item.title || "未命名文案"
                                            }}</span>
                                        </div>
                                        <button
                                            class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 w-5 h-5 flex items-center justify-center rounded-md hover:bg-red-50 hover:text-red-500 text-slate-300 transition-all"
                                            @click.stop="handleRemoveContent(index)">
                                            <Icon name="el-icon-Close" :size="12" />
                                        </button>
                                    </div>
                                    <div
                                        v-if="formData.copywriting.length === 0"
                                        class="h-full flex flex-col items-center justify-center p-4 text-center">
                                        <div class="text-xs font-black text-slate-300 uppercase leading-relaxed">
                                            请添加内容
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 右侧编辑区 -->
                            <div class="flex-1 p-3 overflow-y-auto custom-scrollbar">
                                <template v-if="formData.copywriting[currentActiveIndex]">
                                    <div class="space-y-3 animate-in fade-in duration-300">
                                        <div class="space-y-2">
                                            <label
                                                class="text-[11px] font-black text-slate-400 uppercase tracking-wider ml-1"
                                                >口播标题</label
                                            >
                                            <ElInput
                                                v-model="formData.copywriting[currentActiveIndex].title"
                                                placeholder="输入口播标题..."
                                                maxlength="30"
                                                show-word-limit />
                                        </div>
                                        <div class="space-y-2">
                                            <label
                                                class="text-[11px] font-black text-slate-400 uppercase tracking-wider ml-1"
                                                >口播内容</label
                                            >
                                            <ElInput
                                                v-model="formData.copywriting[currentActiveIndex].content"
                                                type="textarea"
                                                :rows="10"
                                                placeholder="在这里输入或调整您的口播脚本内容..."
                                                resize="none"
                                                maxlength="500"
                                                :class="[
                                                    copywritingErrors[currentActiveIndex] ? 'error-textarea' : '',
                                                ]" />
                                            <div class="flex justify-between items-center pr-2">
                                                <transition name="fade">
                                                    <span
                                                        v-if="copywritingErrors[currentActiveIndex]"
                                                        class="text-[11px] text-red-400 font-medium flex items-center gap-1">
                                                        <Icon name="el-icon-Warning" :size="12" />
                                                        {{ copywritingErrors[currentActiveIndex] }}
                                                    </span>
                                                    <span v-else class="text-[11px] text-slate-300 italic">
                                                        字数需在 3 ~ 500 之间
                                                    </span>
                                                </transition>
                                                <span
                                                    :class="[
                                                        'text-[10px] font-black italic',
                                                        copywritingErrors[currentActiveIndex]
                                                            ? 'text-red-400'
                                                            : 'text-slate-300',
                                                    ]">
                                                    {{ formData.copywriting[currentActiveIndex].content?.length || 0 }}
                                                    / 500
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <div v-else class="h-full flex flex-col items-center justify-center space-y-4">
                                    <p class="text-xs font-medium text-slate-300">点击"手动添加"开始编写内容</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="space-y-3">
                        <div class="bg-slate-50 rounded-[20px] border border-br overflow-hidden">
                            <div class="px-4 py-3 border-b border-slate-100 bg-[#f8fafc]/80">
                                <span class="text-[13px] font-black text-slate-500 uppercase tracking-wider"
                                    >使用设置</span
                                >
                            </div>

                            <div
                                v-for="row in usageToggleRows"
                                :key="row.key"
                                class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
                                <div class="flex flex-col gap-0.5">
                                    <span class="text-[13px] font-medium text-slate-700">{{ row.label }}</span>
                                    <span class="text-[10px] text-slate-400">{{ row.desc }}</span>
                                </div>
                                <div class="flex items-center bg-white border border-slate-200 rounded-xl p-1 gap-1">
                                    <button
                                        v-for="(btn, idx) in row.options"
                                        :key="idx"
                                        @click="(formData.extra as any)[row.key] = idx"
                                        :class="[
                                            'px-3 py-1.5 rounded-lg text-xs font-black transition-all duration-200',
                                            (formData.extra as any)[row.key] === idx ? 'bg-primary text-white shadow-sm' : 'text-slate-400 hover:text-slate-600',
                                        ]">
                                        {{ btn }}
                                    </button>
                                </div>
                            </div>

                            <div class="px-4 py-3 border-b border-slate-100">
                                <div class="flex items-center justify-between">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-[13px] font-medium text-slate-700">视频风格</span>
                                        <span class="text-[10px] text-slate-400">混剪时使用的剪辑风格</span>
                                    </div>
                                    <div
                                        class="flex items-center bg-white border border-slate-200 rounded-xl p-1 gap-1">
                                        <button
                                            v-for="(label, idx) in ['随机', '手动选择']"
                                            :key="idx"
                                            @click="formData.extra.clip = idx"
                                            :class="[
                                                'px-3 py-1.5 rounded-lg text-xs font-black transition-all duration-200',
                                                formData.extra.clip === idx
                                                    ? 'bg-primary text-white shadow-sm'
                                                    : 'text-slate-400 hover:text-slate-600',
                                            ]">
                                            {{ label }}
                                        </button>
                                    </div>
                                </div>
                                <div
                                    v-if="formData.extra.clip === 1"
                                    class="mt-2 h-[44px] rounded-xl bg-white border border-slate-200 flex items-center px-3 cursor-pointer hover:border-primary transition-all group"
                                    @click="openClipStyleDialog">
                                    <span
                                        class="text-primary mr-2 group-hover:scale-110 transition-transform leading-[0]">
                                        <Icon name="el-icon-Film" :size="16" />
                                    </span>
                                    <span class="text-[12px] font-medium text-slate-600 flex-1">
                                        <template v-if="formData.clip.length > 0"
                                            >已选
                                            <span class="text-primary font-black">{{ formData.clip.length }}</span>
                                            个风格</template
                                        >
                                        <template v-else>点击选择视频风格</template>
                                    </span>
                                    <Icon name="el-icon-ArrowRight" color="var(--slate-300)" :size="14" />
                                </div>
                            </div>

                            <div class="px-4 py-3 border-b border-slate-100">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-[13px] font-medium text-slate-700">背景音乐 (BGM)</span>
                                        <span class="text-[10px] text-slate-400">为视频添加背景配乐</span>
                                    </div>
                                </div>
                                <div
                                    class="h-[44px] rounded-xl bg-white border border-slate-200 flex items-center px-3 cursor-pointer hover:border-primary transition-all group"
                                    @click="openMusicDialog">
                                    <span
                                        class="text-primary mr-2 group-hover:scale-110 transition-transform leading-[0]">
                                        <Icon name="el-icon-Headset" :size="16" />
                                    </span>
                                    <span class="text-[12px] font-medium text-slate-600 flex-1 truncate">
                                        {{
                                            formData.music.length > 0
                                                ? `已选 ${formData.music.length} 首音乐`
                                                : "AI音乐库"
                                        }}
                                    </span>
                                    <Icon name="el-icon-ArrowRight" color="var(--slate-300)" :size="14" />
                                </div>
                            </div>
                            <div class="px-4 py-3 border-b border-slate-100">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-[13px] font-medium text-slate-700">口播音色</span>
                                        <span class="text-[10px] text-slate-400">数字人口播时使用的音色</span>
                                    </div>
                                </div>
                                <div
                                    class="h-[44px] rounded-xl bg-white border border-slate-200 flex items-center px-3 cursor-pointer hover:border-primary transition-all group"
                                    @click="openToneDialog">
                                    <span
                                        class="text-primary mr-2 group-hover:scale-110 transition-transform leading-[0]">
                                        <Icon name="el-icon-Microphone" :size="16" />
                                    </span>
                                    <span class="flex-1">
                                        <span
                                            class="text-[12px] font-medium text-slate-600 truncate"
                                            v-if="voiceList.length > 0">
                                            {{ `已选 ${voiceList.length} 个音色` }}
                                        </span>
                                        <span
                                            v-else
                                            class="px-2 py-1.5 rounded-lg bg-[#0065fb]/10 text-primary text-[10px] font-medium"
                                            >视频原音</span
                                        >
                                    </span>

                                    <template v-if="voiceList.length > 0">
                                        <button
                                            class="w-5 h-5 rounded-full hover:bg-red-50 flex items-center justify-center text-slate-300 hover:text-red-400 transition-all mr-1"
                                            @click.stop="voiceList = []">
                                            <Icon name="el-icon-Close" :size="10" />
                                        </button>
                                    </template>
                                    <Icon name="el-icon-ArrowRight" color="var(--slate-300)" :size="14" />
                                </div>
                            </div>

                            <div class="px-4 py-3 border-b border-slate-100">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-[13px] font-medium text-slate-700">BGM 音量</span>
                                        <span class="text-[10px] text-slate-400">背景音乐的音量大小</span>
                                    </div>
                                    <span class="text-[13px] font-black text-primary"
                                        >{{ Math.round(formData.extra.volume * 100) }}%</span
                                    >
                                </div>
                                <ElSlider
                                    v-model="formData.extra.volume"
                                    :min="0"
                                    :max="1"
                                    :step="0.01"
                                    :show-tooltip="false" />
                            </div>

                            <div class="flex items-center justify-between px-4 py-3">
                                <div class="flex flex-col gap-0.5">
                                    <span class="text-[13px] font-medium text-slate-700">素材原声</span>
                                    <span class="text-[10px] text-slate-400">是否保留参考素材的原始声音</span>
                                </div>
                                <ElSwitch v-model="formData.extra.soundSwitch" inactive-color="#e2e8f0" />
                            </div>
                        </div>
                    </section>

                    <div
                        class="bg-slate-50 rounded-[20px] border border-br px-4 py-3 flex items-center justify-between">
                        <div class="flex flex-col gap-0.5">
                            <span class="text-[13px] font-medium text-slate-700">生成数量</span>
                            <span class="text-[10px] text-slate-400">每次任务生成的视频数量</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                @click="handleMinusVideoCount('minus')"
                                :disabled="formData.extra.video_count <= 1"
                                class="w-8 h-8 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-slate-500 hover:border-primary hover:text-primary transition-all disabled:opacity-30 disabled:cursor-not-allowed">
                                <Icon name="el-icon-Minus" :size="14" />
                            </button>
                            <span class="w-10 text-center text-[15px] font-black text-slate-800">{{
                                formData.extra.video_count
                            }}</span>
                            <button
                                @click="handleMinusVideoCount('add')"
                                :disabled="formData.extra.video_count >= 99"
                                class="w-8 h-8 rounded-xl border border-slate-200 bg-white flex items-center justify-center text-slate-500 hover:border-primary hover:text-primary transition-all disabled:opacity-30 disabled:cursor-not-allowed">
                                <Icon name="el-icon-Plus" :size="14" />
                            </button>
                        </div>
                    </div>
                </div>
            </ElScrollbar>
            <div class="mt-2">
                <ElButton
                    class="w-full !h-[50px] !rounded-[18px]"
                    type="primary"
                    size="large"
                    :loading="isSubmitting"
                    :disabled="isSubmitting"
                    @click="handleCreateVideo">
                    <template v-if="isSubmitting">
                        <span class="loading-icon"></span>
                        <span class="text-white font-[1000] text-[15px] tracking-wide ml-3">生成中...</span>
                    </template>
                    <template v-else>
                        <Icon name="el-icon-VideoCamera" color="#fff" :size="20" />
                        <span class="text-white font-[1000] text-[15px] tracking-wide">立即生成视频</span>
                    </template>
                </ElButton>
            </div>
        </div>
    </div>

    <choose-material
        v-if="showChooseMaterial"
        ref="chooseMaterialRef"
        :mode="chooseMaterialMode"
        :type="chooseMaterialType"
        :multiple="true"
        :limit="9"
        @select="handleSelectMaterial"
        @close="showChooseMaterial = false" />
    <choose-character
        v-if="showChooseCharacter"
        ref="chooseCharacterRef"
        @select="handleSelectCharacter"
        @close="showChooseCharacter = false" />
    <choose-tone
        v-if="showToneDialog"
        ref="chooseToneRef"
        :limit="1"
        :model-version="DigitalHumanModelVersionEnum.SHANJIAN"
        :active-tone="voiceList.length > 0 ? voiceList[0] : null"
        :show-free-tone="false"
        @confirm="handleToneConfirm"
        @close="showToneDialog = false" />
    <generate-prompt
        v-if="showGeneratePrompt"
        ref="generatePromptRef"
        :prompt-type="CreateVideoTypeEnum.ORAL_MIX"
        @use-content="getGenerateContent"
        @close="showGeneratePrompt = false" />

    <montage-styles-choose
        v-if="showClipStyleDialog"
        ref="clipStyleDialogRef"
        :selected="formData.clip"
        @confirm="handleClipStyleConfirm"
        @close="showClipStyleDialog = false" />

    <choose-music
        v-if="showMusicDialog"
        ref="chooseMusicRef"
        :selected="formData.music"
        @confirm="handleMusicConfirm"
        @close="showMusicDialog = false" />

    <preview-video v-if="showVideoPreview" ref="videoPreviewPlayerRef" @close="showVideoPreview = false" />
    <ElImageViewer v-if="showImagePreview" :url-list="[imagePreviewUrl]" @close="showImagePreview = false" />
</template>

<script setup lang="ts">
import dayjs from "dayjs";
import { useUserStore } from "@/stores/user";
import { uploadImage } from "@/api/app";
import { getShanjianAnchorList, createShanjianTask } from "@/api/digital_human";
import {
    DigitalHumanModelVersionEnum,
    MontageTypeEnum,
    SidebarTypeEnum,
    CreateVideoTypeEnum,
} from "@/pages/app/digital_human/_enums";
import { montageUploadConfig } from "@/pages/app/digital_human/_config";
import { useMaterial } from "@/pages/app/digital_human/_hooks/useMaterial";
import GeneratePrompt from "@/pages/app/digital_human/_components/generate-prompt.vue";
import MontageStylesChoose from "@/pages/app/digital_human/_components/montage-styles-choose.vue";
import ChooseCharacter from "@/pages/app/digital_human/_components/choose-character.vue";
import ChooseMusic from "@/pages/app/digital_human/_components/choose-music.vue";
import ChooseMaterial from "@/pages/app/digital_human/_components/choose-material.vue";
import ChooseTone from "@/pages/app/digital_human/_components/choose-tone.vue";
import MaterialMenuContent from "@/pages/app/digital_human/_components/material-menu-content.vue";
import MaterialDurationBar from "@/pages/app/digital_human/_components/material-duration-bar.vue";

const userStore = useUserStore();
const { userTokens } = toRefs(userStore);

const formData = reactive<{
    anchorLists: any[];
    materialList: any[];
    copywriting: any[];
    name: string;
    person_name: string;
    person_introduction: string;
    shanjian_type: MontageTypeEnum;
    voice: any[];
    music: any[];
    clip: any[];
    extra: {
        volume: number;
        soundSwitch: boolean;
        human: number;
        music: number;
        clip: number;
        video_count: number;
    };
}>({
    name: dayjs().format("YYYYMMDDHHmm") + "口播混剪",
    anchorLists: [],
    materialList: [],
    copywriting: [],
    person_name: "",
    person_introduction: "",
    shanjian_type: MontageTypeEnum.REAL_PERSON_AI,
    voice: [],
    music: [],
    clip: [],
    extra: { volume: 0.5, soundSwitch: false, human: 0, music: 0, clip: 0, video_count: 1 },
});

const voiceList = ref<any[]>([]);

const usageToggleRows = [
    { key: "human", label: "数字人使用", desc: "多个形象时的出场顺序", options: ["按顺序", "随机"] },
    { key: "music", label: "背景音乐使用", desc: "多首音乐时的播放顺序", options: ["按顺序", "随机"] },
];

const copywritingErrors = computed<Record<number, string | undefined>>(() => {
    const errors: Record<number, string | undefined> = {};
    formData.copywriting.forEach((item, index) => {
        const len = item.content?.trim().length ?? 0;
        if (len === 0) {
            // 空内容不在此处报错，由"请至少添加"校验兜底
            errors[index] = undefined;
        } else if (len < 3) {
            errors[index] = `内容过短，至少需要 3 个字符（当前 ${len} 个）`;
        } else if (len > 500) {
            errors[index] = `内容过长，最多 500 个字符（当前 ${len} 个）`;
        } else {
            errors[index] = undefined;
        }
    });
    return errors;
});

/** 左侧列表容器 ref，用于滚动定位 */
const copywritingListRef = ref<HTMLElement>();

/**
 * 滚动到指定索引的文案条目，并切换激活状态
 */
const scrollToCopywriting = async (index: number) => {
    currentActiveIndex.value = index;
    await nextTick();
    const container = copywritingListRef.value;
    if (!container) return;
    const target = container.querySelector<HTMLElement>(`[data-copywriting-index="${index}"]`);
    console.log(target);
    if (target) {
        target.scrollIntoView({ behavior: "smooth", block: "nearest" });
    }
};
// ──────────────────────────────────────────────────────────────

const calcTotalDuration = () => {
    const videoDur = formData.materialList.reduce(
        (acc: number, item: any) => (item.type === "video" ? acc + (item.duration ?? 0) : acc),
        0
    );
    const imageDur =
        formData.materialList.filter((item: any) => item.type === "image").length * montageUploadConfig.imageDuration;
    return videoDur + imageDur;
};

const anchorQueryParams = reactive({ page_no: 1, page_size: 20, status: [0, 6] });
const { pager: anchorPager, getLists: getAnchorLists } = usePaging({
    fetchFun: getShanjianAnchorList,
    params: anchorQueryParams,
    isScroll: true,
});

const loadMoreAnchor = (e: string) => {
    if (e === "bottom" && (!anchorPager.isLoad || anchorPager.loading)) return;
    anchorQueryParams.page_no++;
    getAnchorLists();
};

const toCloneAnchor = () => navigateTo(`/app/digital_human?type=${SidebarTypeEnum.ANCHOR_CLONE}`);

const toggleSelectAnchor = (item: any) => {
    if (item.status === 0) {
        feedback.msgWarning("该形象正在生成中，请稍后再选择");
        return;
    }
    const index = formData.anchorLists.findIndex((v) => v.id === item.id);
    index > -1 ? formData.anchorLists.splice(index, 1) : formData.anchorLists.push(item);
};

const isAnchorSelected = (id: number) => formData.anchorLists.some((item) => item.id === id);

const showVideoPreview = ref(false);
const videoPreviewPlayerRef = shallowRef();
const handleVideoPlay = async (url: string) => {
    showVideoPreview.value = true;
    await nextTick();
    videoPreviewPlayerRef.value?.open();
    videoPreviewPlayerRef.value?.setUrl(url);
};

const handleDeleteMaterial = (index: number) => formData.materialList.splice(index, 1);

const showChooseMaterial = ref(false);
const chooseMaterialRef = shallowRef<InstanceType<typeof ChooseMaterial>>();
const chooseMaterialType = ref<"image" | "video">("image");
const chooseMaterialMode = ref<"material" | "history">("material");
const supplementPopoverRef = ref();
const addMaterialPopoverRef = ref();

const showImagePreview = ref(false);
const imagePreviewUrl = ref("");

type MaterialAction =
    | { type: "upload-image" | "upload-video"; event: any }
    | { type: "library-image" | "library-video" | "history" };

const handleMaterialAction = async (action: MaterialAction) => {
    supplementPopoverRef.value?.hide?.();
    addMaterialPopoverRef.value?.hide?.();
    if (action.type === "library-image" || action.type === "library-video") {
        chooseMaterialType.value = action.type === "library-image" ? "image" : "video";
        chooseMaterialMode.value = "material";
        showChooseMaterial.value = true;
        await nextTick();
        chooseMaterialRef.value?.open();
    } else if (action.type === "history") {
        chooseMaterialType.value = "video";
        chooseMaterialMode.value = "history";
        showChooseMaterial.value = true;
        await nextTick();
        chooseMaterialRef.value?.open();
    } else if (action.type === "upload-image" || action.type === "upload-video") {
        chooseMaterialType.value = action.type === "upload-image" ? "image" : "video";
        const {
            response: { data },
        } = action.event || {};
        let pic = "";
        let duration = 0;
        if (chooseMaterialType.value === "image") {
            pic = data.uri;
            duration = montageUploadConfig.imageDuration;
        } else {
            const videoResult = await getVideoFirstFrame(data.uri);
            const imageResult = await uploadImage({ file: videoResult.file });
            pic = imageResult.uri;
            duration = videoResult.duration;
        }

        handleMaterialData([{ url: data.uri, type: chooseMaterialType.value, pic, duration }]);
    }
};

const { processAndAppend } = useMaterial(toRef(formData, "materialList"));

const handleSelectMaterial = async (list: any[]) => {
    handleMaterialData(
        list.map((item) => ({ url: item.url, type: chooseMaterialType.value, pic: item.pic, duration: item.duration }))
    );
};

const handleMaterialData = async (list: { url: string; type: string; pic: string; duration: number }[]) => {
    const maxSeconds = montageUploadConfig.materialTotalDuration * 60;
    let skippedCount = 0;
    let currentDuration = calcTotalDuration();

    const filteredList = list.filter((item) => {
        const itemDuration = item.type === "image" ? montageUploadConfig.imageDuration : item.duration ?? 0;

        if (currentDuration + itemDuration > maxSeconds) {
            skippedCount++;
            return false;
        }
        currentDuration += itemDuration;
        return true;
    });

    if (skippedCount > 0) {
        feedback.msgWarning(`${skippedCount} 个素材超出总时长限制已过滤`);
    }

    if (filteredList.length === 0) return;

    await processAndAppend({
        rawList: filteredList,
        urlField: "url",
        type: chooseMaterialType.value,
        maxDuration: 59,
    });
};

const previewMaterial = (item: any) => {
    if (item.type === "video") {
        handleVideoPlay(item.url);
    } else {
        showImagePreview.value = true;
        imagePreviewUrl.value = item.url;
    }
};

const currentActiveIndex = ref(0);
const showChooseCharacter = ref(false);
const chooseCharacterRef = shallowRef<InstanceType<typeof ChooseCharacter>>();
const showGeneratePrompt = ref(false);
const generatePromptRef = shallowRef<InstanceType<typeof GeneratePrompt>>();

const handleAddManual = () => {
    formData.copywriting.push({ title: "", content: "" });
    currentActiveIndex.value = formData.copywriting.length - 1;
};

const openChooseCharacter = async () => {
    showChooseCharacter.value = true;
    await nextTick();
    chooseCharacterRef.value?.open();
};

const handleSelectCharacter = ({ name, introduced }) => {
    formData.person_name = name;
    formData.person_introduction = introduced;
};

const handleRemoveContent = (index: number) => {
    formData.copywriting.splice(index, 1);
    if (currentActiveIndex.value >= formData.copywriting.length) {
        currentActiveIndex.value = Math.max(0, formData.copywriting.length - 1);
    }
};

const getGenerateContent = (list: any[]) => {
    formData.copywriting.push(...list.map((item) => ({ title: item.title, content: item.content })));
    currentActiveIndex.value = 0;
};

const openAiGenerateContent = async () => {
    showGeneratePrompt.value = true;
    await nextTick();
    generatePromptRef.value?.open();
};

const showMusicDialog = ref(false);
const chooseMusicRef = shallowRef<InstanceType<typeof ChooseMusic>>();
const openMusicDialog = async () => {
    showMusicDialog.value = true;
    await nextTick();
    chooseMusicRef.value?.open();
};
const handleMusicConfirm = (data: { music: any[] }) => {
    formData.music = data.music;
};

const showToneDialog = ref(false);
const chooseToneRef = shallowRef<InstanceType<typeof ChooseTone>>();

const openToneDialog = async () => {
    showToneDialog.value = true;
    await nextTick();
    chooseToneRef.value?.open();
};

const handleToneConfirm = (tone: any) => {
    if (!tone) {
        formData.voice = formData.anchorLists.map((item: any) => ({
            voice_id: item.voice_id,
            voice_url: item.voice_url,
            name: item.name,
        }));
    } else {
        voiceList.value = [{ voice_id: tone.voice_id, voice_url: tone.voice_urls, name: tone.name }];
    }
};

const showClipStyleDialog = ref(false);
const clipStyleDialogRef = shallowRef<InstanceType<typeof MontageStylesChoose>>();
const openClipStyleDialog = () => {
    showClipStyleDialog.value = true;
    nextTick(() => clipStyleDialogRef.value?.open());
};
const handleClipStyleConfirm = (data: string[]) => {
    if (data.length === 0) return;
    formData.clip = data;
    showClipStyleDialog.value = false;
};

const handleMinusVideoCount = (type: "minus" | "add") => {
    if (type === "minus" && formData.extra.video_count > 1) formData.extra.video_count--;
    if (type === "add" && formData.extra.video_count < 99) formData.extra.video_count++;
};

const isSubmitting = ref(false);
const handleCreateVideo = async () => {
    if (userTokens.value <= 0) {
        feedback.msgPowerInsufficient();
        return;
    }
    if (!formData.anchorLists.length) return feedback.msgWarning("请至少选择一个形象素材");
    if (!formData.copywriting.length) return feedback.msgWarning("请至少添加一条口播内容");
    if (formData.copywriting.some((item) => !item.content?.trim()))
        return feedback.msgWarning("存在未填写内容的口播文案，请补充完整");

    const invalidIndex = formData.copywriting.findIndex((item) => {
        const len = item.content?.trim().length ?? 0;
        return len < 3 || len > 500;
    });
    if (invalidIndex !== -1) {
        const len = formData.copywriting[invalidIndex].content?.trim().length ?? 0;
        const reason =
            len < 3 ? `过短（当前 ${len} 个字符，至少需要 3 个）` : `过长（当前 ${len} 个字符，最多 500 个）`;
        feedback.msgWarning(`第 ${invalidIndex + 1} 条口播文案内容${reason}，请修改后再提交`);
        await scrollToCopywriting(invalidIndex);
        return;
    }
    // ──────────────────────────────────────────────────────────────

    if (formData.extra.clip === 1 && formData.clip.length === 0)
        return feedback.msgWarning("已选择手动指定风格，请至少选择一个视频风格");
    try {
        isSubmitting.value = true;
        const voice =
            voiceList.value.length > 0
                ? voiceList.value
                : formData.anchorLists.map((item: any) => ({
                      voice_id: item.voice_id,
                      voice_url: item.voice_url,
                      name: item.name,
                  }));

        const params = {
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
            voice: voice,
            material: formData.materialList.map((item: any) => ({ fileUrl: item.url, type: item.type })),
            copywriting: formData.copywriting.map(({ title, content }) => ({ title, content })),
            music: formData.music.map((item: any) => item.content),
            clip: formData.clip.map((item: any) => ({ clip_template_id: item })),
            extra: {
                ...formData.extra,
                volume: formData.extra.volume.toFixed(1),
            },
        };

        await createShanjianTask(params);
        handleCreateSuccess();
    } catch (error: any) {
        feedback.msgError(error?.message || "提交失败，请重试");
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
        onConfirm: () => {
            navigateTo(`/app/digital_human?type=${SidebarTypeEnum.MY_WORKS}`);
        },
    });
};

getAnchorLists();
</script>

<style lang="scss">
.add-material-btn {
    position: relative;
    border: none;
    background: transparent;
    cursor: pointer;
    outline: none;
}

.error-textarea {
    :deep(.el-textarea__inner) {
        border-color: #f87171 !important;
        box-shadow: 0 0 0 2px rgba(248, 113, 113, 0.15) !important;
    }
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
