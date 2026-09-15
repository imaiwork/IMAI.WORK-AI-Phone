<template>
    <div class="h-full flex flex-col overflow-hidden">
        <div class="px-8 pt-6 pb-4 flex items-center gap-3">
            <h2 class="text-xl font-black text-[#1E293B]">爆款玩法复刻</h2>
            <span
                class="px-2 py-0.5 rounded-md bg-red-50 text-red-500 text-xs font-bold inline-flex items-center gap-1"
            >
                <Icon
                    name="local-icon-pv_flame"
                    :size="13"
                    color="#F59E0B"
                />全网热门
            </span>
            <span class="text-xs text-[#94A3B8]"
                >拆解爆火玩法做成模板，换脸即可复刻同款流量</span
            >
            <ElButton
                size="small"
                round
                class="ml-auto"
                @click="showIntro = true"
                >💡 介绍玩法</ElButton
            >
        </div>

        <div class="grow min-h-0 flex gap-6 px-8 pb-6 overflow-hidden">
            <!-- 左：玩法 + 模板墙 -->
            <div class="w-[420px] flex-shrink-0 flex flex-col overflow-hidden">
                <div class="flex gap-2 mb-4">
                    <div
                        v-for="p in PLAYS"
                        :key="p.key"
                        class="flex-1 rounded-xl border-2 p-3 text-center cursor-pointer transition-all"
                        :class="[
                            curPlay === p.key
                                ? 'border-[#2680F7] bg-[#F5F9FF]'
                                : 'border-transparent bg-white',
                            !p.live ? 'opacity-60 cursor-not-allowed' : ''
                        ]"
                        @click="switchPlay(p)"
                    >
                        <Icon
                            :name="`local-icon-${p.icon}`"
                            :size="22"
                            :color="curPlay === p.key ? '#2680F7' : '#8A93A3'"
                        />
                        <div
                            class="text-[13px] font-bold mt-1"
                            :class="
                                curPlay === p.key
                                    ? 'text-[#2680F7]'
                                    : 'text-[#1D2129]'
                            "
                        >
                            {{ p.name }}
                        </div>
                        <div class="text-[11px] text-[#9CA3AF] mt-0.5">
                            {{ p.live ? p.desc : '即将上线' }}
                        </div>
                    </div>
                </div>

                <div
                    class="grow min-h-0 overflow-y-auto bg-white rounded-2xl p-4"
                >
                    <div class="text-sm font-bold text-[#1D2129] mb-1">
                        {{ WALL_TITLE[curPlay] }}
                    </div>
                    <div class="text-xs text-[#9CA3AF] mb-3">
                        {{ WALL_SUB[curPlay] }}
                    </div>

                    <!-- clip 专属：切片分类 -->
                    <div
                        v-if="curPlay === 'clip' && clipCats.length > 1"
                        class="flex flex-wrap gap-1.5 mb-3"
                    >
                        <span
                            v-for="c in clipCats"
                            :key="c"
                            class="px-2.5 py-1 rounded-lg border-2 text-xs cursor-pointer"
                            :class="
                                clipCat === c
                                    ? 'bg-[#EBF2FF] border-[#2680F7] text-[#2680F7] font-semibold'
                                    : 'bg-[#F7F8FA] border-transparent text-[#8A93A3]'
                            "
                            @click="clipCat = c"
                        >
                            {{ c }}
                        </span>
                    </div>

                    <div
                        v-if="tplLoading"
                        class="py-16 text-center text-[#9CA3AF] text-[13px]"
                    >
                        正在加载模板…
                    </div>
                    <div v-else-if="tplError" class="py-16 text-center">
                        <div class="text-[13px] text-[#4B5563] font-semibold">
                            模板加载失败
                        </div>
                        <ElButton
                            size="small"
                            class="mt-3"
                            @click="loadTemplates"
                            >点击重试</ElButton
                        >
                    </div>
                    <div
                        v-else-if="templates.length === 0"
                        class="py-16 text-center"
                    >
                        <div class="text-[26px]">📭</div>
                        <div
                            class="text-[13px] text-[#4B5563] font-semibold mt-2"
                        >
                            {{ tplNotice || '运营还没有上架这个玩法的模板' }}
                        </div>
                        <div class="text-xs text-[#C0C6D0] mt-1">
                            上架后这里会自动出现
                        </div>
                    </div>
                    <div v-else class="grid grid-cols-2 gap-3">
                        <div
                            v-for="t in visibleTemplates"
                            :key="t.ref_id"
                            class="cursor-pointer"
                            @click="picked = t"
                        >
                            <div
                                class="relative rounded-xl overflow-hidden border-2 tpl-cover"
                                :class="
                                    picked?.ref_id === t.ref_id
                                        ? 'border-[#2680F7] shadow-lg'
                                        : 'border-transparent'
                                "
                            >
                                <img
                                    v-if="t.cover_url"
                                    :src="t.cover_url"
                                    class="absolute inset-0 w-full h-full object-cover"
                                />
                                <div
                                    v-else
                                    class="absolute inset-0 flex flex-col items-center justify-center px-3 text-center"
                                >
                                    <span
                                        class="text-white text-sm font-extrabold leading-relaxed"
                                        >{{ t.name }}</span
                                    >
                                    <span class="text-white/70 text-[11px] mt-1"
                                        >{{ t.duration_sec || '--' }}s
                                        示例片段</span
                                    >
                                </div>
                                <span
                                    v-if="t.heat"
                                    class="absolute top-1.5 left-1.5 px-2 py-0.5 rounded-full bg-black/50 text-[#FFD666] text-[11px] font-bold"
                                >
                                    {{ t.heat }}
                                </span>
                                <span
                                    v-if="picked?.ref_id === t.ref_id"
                                    class="absolute top-1.5 right-1.5 w-5 h-5 rounded-full bg-[#2680F7] text-white text-xs flex items-center justify-center"
                                >
                                    ✓
                                </span>
                                <span
                                    v-if="t.video_url"
                                    class="absolute right-1.5 bottom-1.5 w-7 h-7 rounded-full bg-white/90 flex items-center justify-center cursor-pointer"
                                    @click.stop="handlePreview(t)"
                                >
                                    <Icon
                                        name="local-icon-pv_play"
                                        :size="14"
                                        color="#2680F7"
                                        class="ml-0.5"
                                    />
                                </span>
                            </div>
                            <div
                                class="text-[13px] font-semibold text-[#1D2129] text-center mt-1.5 truncate"
                            >
                                {{ t.name }}
                            </div>
                            <div class="text-[11px] text-[#9AA3B2] text-center">
                                {{ tplSub(t) }}
                            </div>
                        </div>
                    </div>

                    <div
                        class="mt-4 bg-[#FFFBEB] rounded-xl p-3 text-[11px] text-[#B45309] leading-relaxed flex gap-1.5"
                    >
                        <Icon
                            name="local-icon-pv_shield_alert"
                            :size="15"
                            color="#F59E0B"
                            class="flex-shrink-0 mt-0.5"
                        />
                        <span>{{ LEGAL_TEXT[curPlay] }}</span>
                    </div>
                </div>
            </div>

            <!-- 右：制作表单 -->
            <div class="grow min-w-0 overflow-y-auto bg-white rounded-2xl p-6">
                <div
                    v-if="!picked"
                    class="h-full flex flex-col items-center justify-center text-[#9CA3AF]"
                >
                    <div class="text-[30px]">👈</div>
                    <div class="text-sm mt-2">先在左侧选一个模板</div>
                </div>
                <template v-else>
                    <div class="text-[15px] font-bold text-[#1D2129] mb-4">
                        已选：{{ picked.name }}
                        <span
                            v-if="picked.duration_sec"
                            class="text-xs text-[#9CA3AF] font-normal ml-2"
                            >{{ picked.duration_sec }}s</span
                        >
                        <span
                            v-if="curPlay === 'face' && originalLines.length"
                            class="text-xs text-[#9CA3AF] font-normal ml-1"
                        >
                            · {{ originalLines.length }} 句台词
                        </span>
                    </div>

                    <!-- 人脸上传（face/dance 共用；clip 不需要人脸）；双人模板要两张：人物A 主角脸、人物B 配角脸 -->
                    <template v-if="curPlay !== 'clip'">
                        <div class="section-title">
                            {{
                                faceSlots.length > 1
                                    ? '上传两位人物的正脸照'
                                    : '上传你的正脸照'
                            }}
                        </div>
                        <div class="flex items-start gap-6 mb-2 flex-wrap">
                            <div
                                v-for="slot in faceSlots"
                                :key="slot.key"
                                class="flex items-center gap-4"
                            >
                                <upload
                                    type="image"
                                    :action="faceUploadAction"
                                    :limit="1"
                                    :show-file-list="false"
                                    :max-size="FACE_IMAGE_MAX_MB"
                                    :accept="FACE_IMAGE_ACCEPT"
                                    @change="handleFaceChange(slot.key, $event)"
                                >
                                    <div
                                        class="w-[110px] h-[110px] rounded-xl flex flex-col items-center justify-center overflow-hidden cursor-pointer"
                                        :class="
                                            faces[slot.key].previewUrl
                                                ? ''
                                                : 'border-2 border-dashed border-[#C9D2E0] bg-[#FAFBFD]'
                                        "
                                    >
                                        <img
                                            v-if="faces[slot.key].previewUrl"
                                            :src="faces[slot.key].previewUrl"
                                            class="w-full h-full object-cover"
                                        />
                                        <template v-else>
                                            <span
                                                class="text-[22px] text-[#C9D2E0]"
                                                >+</span
                                            >
                                            <span
                                                class="text-[11px] text-[#9CA3AF]"
                                                >{{
                                                    slot.label || '选择照片'
                                                }}</span
                                            >
                                        </template>
                                    </div>
                                </upload>
                                <div
                                    class="text-xs text-[#4B5563] leading-relaxed"
                                >
                                    <div
                                        v-if="slot.label"
                                        class="font-semibold text-[#1D2129] mb-1"
                                    >
                                        {{ slot.label }}
                                    </div>
                                    正脸、五官清晰、无遮挡<br />JPG/PNG/WEBP，≤{{
                                        FACE_IMAGE_MAX_MB
                                    }}MB
                                    <div
                                        v-if="faces[slot.key].previewUrl"
                                        class="mt-2 text-[#9CA3AF]"
                                    >
                                        再次点击照片可重新上传
                                    </div>
                                </div>
                            </div>
                        </div>
                        <ElCheckbox v-model="consent" class="mb-5"
                            >我确认使用<b>本人授权</b>的人脸照片</ElCheckbox
                        >
                    </template>

                    <!-- face：台词 + 音色 -->
                    <template v-if="curPlay === 'face'">
                        <div class="section-title">
                            逐句改写台词
                            <span
                                class="text-xs font-normal"
                                :class="
                                    speechOver
                                        ? 'text-[#EF4444] font-bold'
                                        : 'text-[#9CA3AF]'
                                "
                            >
                                预计口播 {{ speechSeconds }}s /
                                {{ MAX_SPEECH_SECONDS }}s
                            </span>
                        </div>
                        <div class="flex items-center mb-3 -mt-2">
                            <span class="text-xs text-[#9CA3AF]">{{
                                scriptHint
                            }}</span>
                            <ElButton
                                type="primary"
                                size="small"
                                round
                                class="ml-auto"
                                @click="showPersona = true"
                            >
                                <Icon
                                    name="local-icon-pv_sparkles"
                                    :size="13"
                                    color="#fff"
                                    class="mr-1"
                                />一键替换台词
                            </ElButton>
                        </div>
                        <div
                            v-for="(line, i) in lines"
                            :key="i"
                            class="flex items-start gap-2 mb-2"
                            :class="isRight(line, i) ? 'flex-row-reverse' : ''"
                        >
                            <span
                                class="w-6 h-6 rounded-full text-[11px] font-bold text-white flex items-center justify-center flex-shrink-0 mt-1"
                                :class="
                                    isRight(line, i)
                                        ? 'bg-[#2680F7]'
                                        : 'bg-[#3A4558]'
                                "
                            >
                                {{
                                    (
                                        line.role ||
                                        (isRight(line, i) ? 'B' : 'A')
                                    ).slice(0, 1)
                                }}
                            </span>
                            <div
                                class="max-w-[70%] flex-1"
                                :class="
                                    isRight(line, i)
                                        ? 'flex flex-col items-end'
                                        : ''
                                "
                            >
                                <ElInput
                                    v-model="line.text"
                                    type="textarea"
                                    :rows="1"
                                    autosize
                                    maxlength="200"
                                    placeholder="改成你的词…"
                                    :class="
                                        isChanged(line, i)
                                            ? 'pv-line-changed'
                                            : ''
                                    "
                                />
                                <span
                                    v-if="isChanged(line, i)"
                                    class="text-[11px] text-[#2680F7] mt-0.5"
                                    >已改写</span
                                >
                            </div>
                            <ElButton
                                v-if="lines.length > 1"
                                text
                                size="small"
                                class="mt-1"
                                @click="lines.splice(i, 1)"
                                >删</ElButton
                            >
                        </div>
                        <div class="flex gap-2 mb-5">
                            <ElButton
                                v-if="lines.length < MAX_LINES"
                                size="small"
                                @click="addLine"
                                >+ 加一句</ElButton
                            >
                            <ElButton
                                v-if="originalLines.length"
                                size="small"
                                @click="resetScript"
                                >恢复原片台词</ElButton
                            >
                        </div>

                        <div class="section-title">配音音色</div>
                        <div class="flex flex-wrap gap-2 mb-6">
                            <span
                                v-for="v in VOICES"
                                :key="v"
                                class="px-3.5 py-1.5 rounded-lg border-2 text-xs cursor-pointer"
                                :class="
                                    voice === v
                                        ? 'bg-[#EBF2FF] border-[#2680F7] text-[#2680F7] font-semibold'
                                        : 'bg-[#F7F8FA] border-transparent text-[#8A93A3]'
                                "
                                @click="voice = v"
                            >
                                {{ v }}
                            </span>
                        </div>
                    </template>

                    <!-- dance：背景 + 口播 -->
                    <template v-else-if="curPlay === 'dance'">
                        <template v-if="picked.allow_bg_replace">
                            <div class="section-title">视频背景</div>
                            <ElRadioGroup v-model="bgMode" class="mb-3">
                                <ElRadioButton value="keep"
                                    >保持原背景</ElRadioButton
                                >
                                <ElRadioButton value="upload"
                                    >我的背景</ElRadioButton
                                >
                            </ElRadioGroup>
                            <div
                                v-if="bgMode === 'upload'"
                                class="flex items-center gap-3 mb-4"
                            >
                                <upload
                                    type="image"
                                    :action="faceUploadAction"
                                    :limit="1"
                                    :show-file-list="false"
                                    :max-size="FACE_IMAGE_MAX_MB"
                                    :accept="FACE_IMAGE_ACCEPT"
                                    @change="handleFaceChange('bg', $event)"
                                >
                                    <div
                                        class="w-[90px] h-[90px] rounded-xl flex items-center justify-center overflow-hidden cursor-pointer"
                                        :class="
                                            bg.previewUrl
                                                ? ''
                                                : 'border-2 border-dashed border-[#C9D2E0] bg-[#FAFBFD]'
                                        "
                                    >
                                        <img
                                            v-if="bg.previewUrl"
                                            :src="bg.previewUrl"
                                            class="w-full h-full object-cover"
                                        />
                                        <span
                                            v-else
                                            class="text-[11px] text-[#9CA3AF]"
                                            >上传背景图</span
                                        >
                                    </div>
                                </upload>
                                <span class="text-xs text-[#4B5563]"
                                    >上传你的门店/场景照片，人物会跳进这个背景里</span
                                >
                            </div>
                        </template>

                        <div class="section-title flex items-center gap-3">
                            片尾口播
                            <ElSwitch v-model="speechOn" size="small" />
                        </div>
                        <div
                            class="text-xs text-[#9CA3AF] mb-3 -mt-1 leading-relaxed"
                        >
                            开启后会对口型配音引导关注/进店。<span
                                class="text-[#F59E0B]"
                                >注意：成片时长会跟着这句话走</span
                            >，话短片子就短，想保留完整舞蹈就关掉它
                        </div>
                        <template v-if="speechOn">
                            <ElInput
                                v-model="speechText"
                                :maxlength="MAX_SPEECH_RUNES"
                                show-word-limit
                                :placeholder="`至少 ${MIN_SPEECH_RUNES} 字，例：关注我，明天教你下一支`"
                                class="mb-2 max-w-[420px]"
                            />
                            <div class="flex flex-wrap gap-2 mb-2">
                                <span
                                    v-for="v in VOICES"
                                    :key="v"
                                    class="px-3 py-1 rounded-lg border-2 text-xs cursor-pointer"
                                    :class="
                                        voice === v
                                            ? 'bg-[#EBF2FF] border-[#2680F7] text-[#2680F7] font-semibold'
                                            : 'bg-[#F7F8FA] border-transparent text-[#8A93A3]'
                                    "
                                    @click="voice = v"
                                >
                                    {{ v }}
                                </span>
                            </div>
                            <div class="text-[11px] text-[#F59E0B] mb-4">
                                提示：开口播后成片时长以口播音频为准
                            </div>
                        </template>
                    </template>

                    <!-- clip：你的视频 + 保留时长 + 转场 + 音效/钩子 -->
                    <template v-else>
                        <div class="section-title">上传你的视频</div>
                        <div class="text-xs text-[#9CA3AF] mb-3 -mt-2">
                            切片播完后接上它，整段播完成片就多长（两段都保留原声）
                        </div>
                        <div class="flex items-center gap-4 mb-5">
                            <upload
                                type="video"
                                show-progress
                                :limit="1"
                                :show-file-list="false"
                                :max-size="CLIP_VIDEO_MAX_MB"
                                :max-duration="CLIP_VIDEO_MAX_SEC"
                                :accept="CLIP_VIDEO_ACCEPT"
                                @change="handleClipVideoChange"
                            >
                                <div
                                    class="w-[240px] rounded-xl flex flex-col items-center justify-center cursor-pointer py-6"
                                    :class="
                                        clipVideo.uri
                                            ? 'bg-[#F5F9FF] border-2 border-[#2680F7]'
                                            : 'border-2 border-dashed border-[#C9D2E0] bg-[#FAFBFD]'
                                    "
                                >
                                    <template v-if="clipVideo.uri">
                                        <span
                                            class="text-[13px] font-semibold text-[#2680F7] px-3 truncate max-w-full"
                                            >✓
                                            {{
                                                clipVideo.name || '已上传视频'
                                            }}</span
                                        >
                                        <span
                                            class="text-[11px] text-[#9CA3AF] mt-1"
                                            >点击可重新上传</span
                                        >
                                    </template>
                                    <template v-else>
                                        <span class="text-[22px] text-[#C9D2E0]"
                                            >+</span
                                        >
                                        <span class="text-[11px] text-[#9CA3AF]"
                                            >选择视频（MP4/MOV，≤{{
                                                CLIP_VIDEO_MAX_MB
                                            }}MB、≤{{
                                                CLIP_VIDEO_MAX_SEC
                                            }}秒）</span
                                        >
                                    </template>
                                </div>
                            </upload>
                            <!-- 成片时长跟用户视频长度走，传完就亮预估，别等扣完才知道 -->
                            <div
                                v-if="clipVideo.uri && clipVideo.duration"
                                class="text-xs text-[#6B7280]"
                            >
                                成片约
                                {{ Math.ceil(keepSec + clipVideo.duration) }}
                                秒，按实际时长计费、多退少补
                            </div>
                        </div>

                        <div class="section-title">切片保留时长</div>
                        <div class="flex gap-2 mb-5 max-w-[420px]">
                            <span
                                v-for="sec in KEEP_OPTIONS"
                                :key="sec"
                                class="flex-1 py-2 rounded-lg border-2 text-[13px] text-center font-bold"
                                :class="[
                                    keepSec === sec
                                        ? 'bg-[#EBF2FF] border-[#2680F7] text-[#2680F7]'
                                        : 'bg-[#F7F8FA] border-transparent text-[#4B5563]',
                                    keepDisabled(sec)
                                        ? 'opacity-40 cursor-not-allowed'
                                        : 'cursor-pointer'
                                ]"
                                @click="handlePickKeep(sec)"
                            >
                                {{ sec }}s
                            </span>
                        </div>

                        <div class="section-title">拼接转场</div>
                        <div class="flex gap-2 mb-5 max-w-[420px]">
                            <span
                                v-for="t in TRANSITIONS"
                                :key="t.key"
                                class="flex-1 py-2 rounded-lg border-2 text-center cursor-pointer"
                                :class="
                                    transition === t.key
                                        ? 'bg-[#EBF2FF] border-[#2680F7]'
                                        : 'bg-[#F7F8FA] border-transparent'
                                "
                                @click="transition = t.key"
                            >
                                <div
                                    class="text-xs font-semibold"
                                    :class="
                                        transition === t.key
                                            ? 'text-[#2680F7]'
                                            : 'text-[#8A93A3]'
                                    "
                                >
                                    {{ t.name }}
                                </div>
                                <div class="text-[10px] text-[#C0C6D0]">
                                    {{ t.desc }}
                                </div>
                            </span>
                        </div>

                        <div class="section-title flex items-center gap-3">
                            拼接重音音效
                            <ElSwitch v-model="sfxOn" size="small" />
                        </div>
                        <div class="text-xs text-[#9CA3AF] mb-4 -mt-1">
                            切换瞬间一记低频重击，强化卡点
                        </div>

                        <div class="section-title flex items-center gap-3">
                            钩子文案
                            <ElSwitch v-model="hookOn" size="small" />
                        </div>
                        <div class="text-xs text-[#9CA3AF] mb-2 -mt-1">
                            压在切片结尾 2.5~4.5
                            秒（按字数自动延长），把人勾进你的视频
                        </div>
                        <ElInput
                            v-if="hookOn"
                            v-model="hookText"
                            maxlength="20"
                            show-word-limit
                            placeholder="例：看到最后我人傻了…"
                            class="mb-4 max-w-[420px]"
                        />

                        <!-- 成片结构条 -->
                        <div
                            class="bg-[#F7F8FA] rounded-xl p-3 mb-5 max-w-[560px]"
                        >
                            <div class="flex h-8 rounded-lg overflow-hidden">
                                <div
                                    class="flex items-center justify-center w-[38%] clip-bar"
                                >
                                    <span
                                        class="text-[11px] text-white font-bold"
                                        >切片 {{ keepSec }}s</span
                                    >
                                </div>
                                <div
                                    class="flex-1 flex items-center justify-center bg-[#EBF2FF]"
                                >
                                    <span
                                        class="text-[11px] text-[#2680F7] font-bold"
                                        >你的视频（整段播完）</span
                                    >
                                </div>
                            </div>
                            <div
                                class="text-[11px] text-[#9CA3AF] mt-2 leading-relaxed"
                            >
                                前 {{ keepSec }} 秒放切片{{
                                    transitionName
                                }}后接你的视频完整播完（两段都保留原声）；你的视频多长成片就多长
                            </div>
                        </div>
                    </template>

                    <div class="pt-2 flex items-stretch gap-2.5 max-w-[560px]">
                        <div
                            class="flex items-center gap-2.5 bg-white border border-[#E5E9F0] rounded-[14px] px-2.5 cursor-pointer hover:border-[#0065fb]/30 transition shrink-0"
                            @click="showTokensCost = true"
                        >
                            <div
                                class="w-[30px] h-[30px] rounded-[10px] bg-gradient-to-br from-[#0065fb] to-[#0ea5e9] flex items-center justify-center shrink-0"
                            >
                                <Icon
                                    name="el-icon-StarFilled"
                                    color="#fff"
                                    :size="16"
                                />
                            </div>
                            <div class="flex flex-col gap-0.5">
                                <span
                                    class="text-xs font-medium text-slate-700 leading-none"
                                    >算力消耗</span
                                >
                                <span class="text-[10px] text-slate-400"
                                    >点击查看详细计费</span
                                >
                            </div>
                        </div>
                        <ElButton
                            class="flex-1 !h-auto"
                            type="primary"
                            size="large"
                            :loading="submitting"
                            @click="lockGenerate(false)"
                        >
                            {{ curPlay === 'clip' ? '合成视频' : '生成同款' }}
                        </ElButton>
                    </div>
                    <div class="text-[11px] text-[#C0C6D0] mt-2">
                        生成失败自动退还算力
                    </div>
                </template>
            </div>
        </div>

        <persona-dialog
            v-model="showPersona"
            :applied="appliedPersona"
            :rewriting="rewriting"
            @apply="applyPersona"
        />
        <intro-dialog v-model="showIntro" :play="curPlay" />
        <cost-pop v-model="showTokensCost" :type="costType" />

        <!-- 模板预览 -->
        <ElDialog
            v-model="showPreview"
            width="380px"
            align-center
            title="模板预览"
            destroy-on-close
        >
            <video
                v-if="previewUrl"
                :src="previewUrl"
                controls
                autoplay
                class="w-full rounded-xl bg-black max-h-[560px]"
            />
        </ElDialog>

        <!-- 生成浮层 -->
        <ElDialog
            v-model="gen.show"
            :close-on-click-modal="false"
            :show-close="false"
            width="460px"
            align-center
        >
            <div v-if="gen.phase === 'running'" class="text-center py-2">
                <div class="text-base font-bold text-[#0D1117]">正在生成…</div>
                <div class="text-xs text-[#9CA3AF] mt-1">
                    {{ gen.stepLabel || '排队等待生成' }} · 已用
                    {{ gen.elapsed }}s
                </div>
                <ElProgress
                    :percentage="Math.round(gen.percent)"
                    :stroke-width="8"
                    class="mt-4"
                />
                <div class="text-[11px] text-[#C0C6D0] mt-2">
                    整条链路约 2~5
                    分钟，可以留在这个窗口等；也可后台运行，完成后到「我的作品」查看
                </div>
                <ElButton class="mt-4" @click="handleGenBack"
                    >后台运行</ElButton
                >
            </div>
            <div v-else-if="gen.phase === 'success'" class="text-center py-2">
                <div class="text-base font-bold text-[#0D1117] mb-3">
                    生成完成 🎉
                </div>
                <video
                    v-if="gen.videoUrl"
                    :src="gen.videoUrl"
                    controls
                    class="w-full rounded-xl bg-black max-h-[420px]"
                />
                <div class="text-[11px] text-[#9CA3AF] mt-2">
                    共消耗 {{ gen.tokensCost }} 算力 · 可在「我的作品」中回看
                </div>
                <div class="flex justify-center gap-3 mt-4">
                    <ElButton @click="handleGenBack">完成</ElButton>
                    <ElButton type="primary" @click="handleGenBack"
                        >再生成一条</ElButton
                    >
                </div>
            </div>
            <div v-else class="text-center py-2">
                <div class="text-base font-bold text-[#0D1117] mb-3">
                    生成失败
                </div>
                <div
                    class="bg-[#FFF3F0] rounded-xl p-3 text-xs text-[#D93025] leading-relaxed text-left"
                >
                    {{ gen.failReason || '上游未返回原因，请稍后重试' }}
                </div>
                <div
                    v-if="gen.refunded"
                    class="text-[11px] text-[#16A34A] mt-2"
                >
                    算力已自动退还
                </div>
                <div class="flex justify-center gap-3 mt-4">
                    <ElButton @click="closeGen">关闭</ElButton>
                    <ElButton
                        type="primary"
                        :loading="submitting"
                        @click="lockGenerate(true)"
                        >重试</ElButton
                    >
                </div>
            </div>
        </ElDialog>
    </div>
</template>

<script setup lang="ts">
import {
    getPlayVideoTemplates,
    submitPlayVideo,
    getPlayVideoDetail,
    rewritePlayVideoLines
} from '@/api/play_video'
import feedback from '@/utils/feedback'
import { getApiUrl, getApiPrefix } from '@/utils/env'
import { RequestCodeEnum } from '@/enums/requestEnums'
import { useLockFn } from '@/composables/useLockFn'
import CostPop from '@/pages/app/digital_human/_components/cost-pop.vue'
import { MontageTypeEnum } from '@/pages/app/digital_human/_enums'
import { getValidUploadFileData } from '@/pages/app/digital_human/_hooks/useUpload'
import PersonaDialog from './_components/persona-dialog.vue'
import IntroDialog from './_components/intro-dialog.vue'
import type { PlayKey, PlayLine, PlayPersona, PlayTemplate } from './_typings'
import {
    PLAYS,
    LEGAL_TEXT,
    WALL_TITLE,
    WALL_SUB,
    VOICES,
    KEEP_OPTIONS,
    TRANSITIONS,
    MAX_LINES,
    MAX_SPEECH_RUNES,
    MIN_SPEECH_RUNES,
    MAX_SPEECH_SECONDS,
    SPEECH_RUNES_PER_SECOND,
    FACE_IMAGE_MAX_MB,
    FACE_IMAGE_ACCEPT,
    CLIP_VIDEO_MAX_MB,
    CLIP_VIDEO_MAX_SEC,
    TEMPLATE_OFFLINE_HINT,
    CLIP_VIDEO_ACCEPT,
    POLL_INTERVAL_MS,
    POLL_BACKOFF_MS,
    PERCENT_CAP,
    EXPECT_SECONDS,
    PlayTaskStatusEnum,
    type TransitionKey
} from './_enums'

const showIntro = ref(false)
const showTokensCost = ref(false)
const showPreview = ref(false)
const previewUrl = ref('')
const handlePreview = (t: PlayTemplate) => {
    if (!t.video_url) return
    previewUrl.value = t.video_url
    showPreview.value = true
}

const curPlay = ref<PlayKey>('face')
const templates = ref<PlayTemplate[]>([])
const tplLoading = ref(false)
const tplError = ref(false)
const tplNotice = ref('')
const picked = ref<PlayTemplate | null>(null)

const consent = ref(false)
const lines = ref<PlayLine[]>([])
// 原片台词基线（「已改写」diff 用）
const originalLines = ref<string[]>([])
const voice = ref(VOICES[0])
const bgMode = ref<'keep' | 'upload'>('keep')
const speechOn = ref(false)
const speechText = ref('')
const emptyFile = () => ({ fileId: 0, previewUrl: '' })
const face = reactive(emptyFile())
const face2 = reactive(emptyFile())
const bg = reactive(emptyFile())
type FaceSlotKey = 'face' | 'face2' | 'bg'
const faces: Record<FaceSlotKey, { fileId: number; previewUrl: string }> = {
    face,
    face2,
    bg
}
// 双人模板（face_count>=2）要两张脸：人物A 主角、人物B 配角；单人模板只要一张
const faceSlots = computed(() =>
    curPlay.value === 'face' && (picked.value?.face_count || 1) >= 2
        ? [
              { key: 'face' as FaceSlotKey, label: '人物A（主角）' },
              { key: 'face2' as FaceSlotKey, label: '人物B（配角）' }
          ]
        : [{ key: 'face' as FaceSlotKey, label: '' }]
)

// ── clip 专属状态 ──
const DEFAULT_KEEP_SEC = 15
const DEFAULT_HOOK_TEXT = '看到最后我人傻了…'
const emptyClipVideo = () => ({ uri: '', name: '', duration: 0 })
const clipVideo = reactive(emptyClipVideo())
const keepSec = ref(DEFAULT_KEEP_SEC)
const transition = ref<TransitionKey>('none')
const sfxOn = ref(true)
const hookOn = ref(true)
const hookText = ref(DEFAULT_HOOK_TEXT)
const transitionName = computed(() => {
    const t = TRANSITIONS.find((x) => x.key === transition.value)
    return t && t.key !== 'none' ? `「${t.name}」` : '硬切'
})
// 档位超过切片素材时长则禁用（原型行为）
const keepDisabled = (sec: number) =>
    !!picked.value?.duration_sec && sec > (picked.value.duration_sec || 0)
const handlePickKeep = (sec: number) => {
    if (!keepDisabled(sec)) keepSec.value = sec
}

// clip 分类（按实际模板去重；切分类后已选不在列表则回落第一条）
const clipCat = ref('全部')
const clipCats = computed(() => [
    '全部',
    ...new Set(
        templates.value.map((t) => t.category).filter(Boolean) as string[]
    )
])
const visibleTemplates = computed(() =>
    curPlay.value === 'clip' && clipCat.value !== '全部'
        ? templates.value.filter((t) => t.category === clipCat.value)
        : templates.value
)
watch([clipCat, visibleTemplates], () => {
    const list = visibleTemplates.value
    if (
        list.length &&
        (!picked.value || !list.some((t) => t.ref_id === picked.value?.ref_id))
    ) {
        picked.value = list[0]
    }
})

const costType = computed(() =>
    curPlay.value === 'face'
        ? MontageTypeEnum.PLAY_FACE
        : curPlay.value === 'dance'
        ? MontageTypeEnum.PLAY_DANCE
        : MontageTypeEnum.PLAY_SPLICE
)

// 台词按预估口播时长限制(与后端一致)
const speechSeconds = computed(() => {
    const total = lines.value.reduce(
        (n, l) => n + (l.text || '').trim().length,
        0
    )
    return Math.ceil(total / SPEECH_RUNES_PER_SECOND)
})
const speechOver = computed(() => speechSeconds.value > MAX_SPEECH_SECONDS)

const fillLinesFromTemplate = (tpl: PlayTemplate | null) => {
    const script = (tpl?.script || []).slice(0, MAX_LINES)
    originalLines.value = script.map((s) => (s.text || '').trim())
    lines.value = script.map((s, i) => ({
        role: s.role || s.name || (i % 2 ? 'B' : 'A'),
        text: s.text || ''
    }))
}

// 模板选中后按台本预填台词（留基线做「已改写」diff）
watch(picked, (tpl) => {
    fillLinesFromTemplate(tpl)
    if (curPlay.value === 'face' && lines.value.length === 0) addLine()
    // clip：默认档位不超过素材时长
    if (curPlay.value === 'clip' && keepDisabled(keepSec.value)) {
        const ok = KEEP_OPTIONS.filter((sec) => !keepDisabled(sec))
        if (ok.length) keepSec.value = ok[ok.length - 1]
    }
})

const isRight = (line: PlayLine, i: number) =>
    line.role ? line.role === 'B' : i % 2 === 1
const isChanged = (line: PlayLine, i: number) =>
    i >= originalLines.value.length ||
    (line.text || '').trim() !== originalLines.value[i]
const changedCount = computed(
    () => lines.value.filter((l, i) => isChanged(l, i)).length
)
const scriptHint = computed(() => {
    if (appliedPersona.value)
        return `已按「${appliedPersona.value.persona_name}」替换全部台词，每句仍可单独修改`
    if (!originalLines.value.length) return '点任意一句直接改写'
    return changedCount.value
        ? `已手动改写 ${changedCount.value} 句，不满意可恢复原片台词`
        : '点任意一句直接改写台词；或选人设一键替换'
})

// ── 一键替换台词：AI 改写（站长 rewriteLines）；失败回落本地拼接规则——
// 前句原样保留，末句接「——{行业}这事，{人设名}，{转化钩子}。」
const showPersona = ref(false)
const appliedPersona = ref<PlayPersona | null>(null)
let appliedSnapshot = ''

const localRewrite = (p: PlayPersona) => {
    const base = originalLines.value.length
        ? originalLines.value
        : lines.value.map((l) => l.text)
    const rewritten = [...base]
    const last = rewritten[rewritten.length - 1].replace(/[。！？.!?～~]+$/, '')
    const parts = [p.industry ? `${p.industry}这事` : '这事', p.persona_name]
    if ((p.conversion_hook || '').trim())
        parts.push((p.conversion_hook || '').trim())
    rewritten[rewritten.length - 1] = `${last}——${parts.join('，')}。`
    return rewritten.map((text, i) => ({
        role: lines.value[i]?.role || (i % 2 ? 'B' : 'A'),
        text
    }))
}

const rewriting = ref(false)
const applyPersona = async (p: PlayPersona) => {
    if (rewriting.value) return
    const current = lines.value.map((l) => ({
        role: l.role,
        text: (l.text || '').trim()
    }))
    if (current.length === 0 || current.some((l) => !l.text)) {
        feedback.msgWarning('先把台词补全再替换')
        return
    }
    rewriting.value = true
    try {
        const data: any = await rewritePlayVideoLines({
            persona_id: p.id,
            lines: current,
            template_name: picked.value?.name || ''
        })
        lines.value = (data.lines || []).map((l: any, i: number) => ({
            role: l.role || current[i]?.role || (i % 2 ? 'B' : 'A'),
            text: l.text || ''
        }))
        feedback.msgSuccess(
            `已按「${p.persona_name}」替换 ${lines.value.length} 句台词`
        )
    } catch (e: any) {
        // AI 失败 → 本地拼接兜底（保底体验不断）
        lines.value = localRewrite(p)
        feedback.msgWarning('AI 繁忙，已用快速模式替换')
    } finally {
        rewriting.value = false
        appliedPersona.value = p
        appliedSnapshot = lines.value.map((l) => l.text).join('\n')
        showPersona.value = false
    }
}

watch(
    lines,
    () => {
        if (
            appliedPersona.value &&
            lines.value.map((l) => l.text).join('\n') !== appliedSnapshot
        ) {
            appliedPersona.value = null
        }
    },
    { deep: true }
)

const resetScript = () => {
    fillLinesFromTemplate(picked.value)
    if (lines.value.length === 0) addLine()
    appliedPersona.value = null
    feedback.msgSuccess('已恢复原片台词')
}

const tplSub = (t: PlayTemplate) => {
    const head =
        curPlay.value === 'face'
            ? (t.face_count || 1) >= 2
                ? '双人'
                : '单人'
            : t.category || '热门'
    return t.duration_sec ? `${head} · ${t.duration_sec}s` : head
}

// 表单回到初始值：切玩法、离开生成流程时都不带上一次的输入
const resetForm = () => {
    consent.value = false
    lines.value = []
    originalLines.value = []
    voice.value = VOICES[0]
    bgMode.value = 'keep'
    speechOn.value = false
    speechText.value = ''
    Object.assign(face, emptyFile())
    Object.assign(face2, emptyFile())
    Object.assign(bg, emptyFile())
    Object.assign(clipVideo, emptyClipVideo())
    keepSec.value = DEFAULT_KEEP_SEC
    transition.value = 'none'
    sfxOn.value = true
    hookOn.value = true
    hookText.value = DEFAULT_HOOK_TEXT
    appliedPersona.value = null
    appliedSnapshot = ''
}

// 进入某个玩法：清表单、重拉模板墙（自动选中第一条）
const enterPlay = (key: PlayKey) => {
    curPlay.value = key
    picked.value = null
    clipCat.value = '全部'
    resetForm()
    loadTemplates()
}

const switchPlay = (p: (typeof PLAYS)[number]) => {
    if (!p.live) {
        feedback.msgWarning(`${p.name}即将上线，敬请期待`)
        return
    }
    if (curPlay.value === p.key) return
    enterPlay(p.key)
}

const loadTemplates = async () => {
    const play = curPlay.value
    tplLoading.value = true
    tplError.value = false
    try {
        const data: any = await getPlayVideoTemplates({ play })
        if (play !== curPlay.value) return // 切玩法后旧请求晚返回，丢弃
        templates.value = data.templates || []
        tplNotice.value = data.notice || ''
        if (
            !picked.value ||
            !templates.value.some((t) => t.ref_id === picked.value?.ref_id)
        ) {
            picked.value = templates.value[0] || null
        }
    } catch (e) {
        if (play !== curPlay.value) return
        tplError.value = true
        templates.value = []
    } finally {
        if (play === curPlay.value) tplLoading.value = false
    }
}

const addLine = () => {
    if (lines.value.length >= MAX_LINES) return
    lines.value.push({ role: lines.value.length % 2 ? 'B' : 'A', text: '' })
}

// ── 上传：人脸/背景走站长人脸素材接口（公共 upload 组件带 token，limit=1 时再选即替换）──
const faceUploadAction = `${getApiUrl()}${getApiPrefix()}/playVideo/uploadFace`
const handleFaceChange = (kind: FaceSlotKey, file: any) => {
    const target = faces[kind]
    const res = file?.response
    const data = res?.data
    if (
        !res ||
        Number(res.code) !== RequestCodeEnum.SUCCESS ||
        !data?.face_file_id
    ) {
        feedback.msgError(res?.msg || '上传失败')
        return
    }
    target.fileId = Number(data.face_file_id)
    target.previewUrl = data.preview_url || ''
}

// clip：用户视频走站内 /upload/video（契约 resp.uri）
const handleClipVideoChange = (file: any) => {
    const data = getValidUploadFileData(file)
    if (!data) {
        feedback.msgError(file?.response?.msg || '上传失败')
        return
    }
    clipVideo.uri = data.uri || data.url || ''
    clipVideo.name = data.name || file?.name || ''
    clipVideo.duration = Number(data.duration) || 0
}

// ── 生成流程（92% 封顶 + 5s 轮询 8s 退避；同 sn 重试幂等） ──
const gen = reactive({
    show: false,
    phase: 'running' as 'running' | 'success' | 'fail',
    stepLabel: '',
    percent: 6,
    elapsed: 0,
    sn: '',
    videoUrl: '',
    failReason: '',
    refunded: false,
    tokensCost: 0
})
let timer: ReturnType<typeof setInterval> | null = null
let pollTimer: ReturnType<typeof setTimeout> | null = null
let currentSn = ''

const stopTimers = () => {
    if (timer) {
        clearInterval(timer)
        timer = null
    }
    if (pollTimer) {
        clearTimeout(pollTimer)
        pollTimer = null
    }
}

const closeGen = () => {
    gen.show = false
    stopTimers()
}

// 后台运行 / 完成 / 再生成一条：收起浮层，回到刚进入页面时的样子（任务在后台继续跑）
const handleGenBack = () => {
    closeGen()
    enterPlay(PLAYS[0].key)
}

const poll = (delay = POLL_INTERVAL_MS) => {
    pollTimer = setTimeout(async () => {
        if (!gen.show || gen.phase !== 'running') return
        try {
            const data: any = await getPlayVideoDetail({ sn: gen.sn })
            if (data.status === PlayTaskStatusEnum.SUCCESS) {
                gen.percent = 100
                gen.videoUrl = data.video_url || ''
                gen.phase = 'success'
                stopTimers()
                return
            }
            if (
                data.status === PlayTaskStatusEnum.FAILED ||
                data.status === PlayTaskStatusEnum.TIMEOUT
            ) {
                gen.failReason = data.fail_reason || '生成失败'
                gen.refunded = !!data.refunded
                gen.phase = 'fail'
                stopTimers()
                return
            }
            if (data.step_label) gen.stepLabel = data.step_label
            poll(POLL_INTERVAL_MS)
        } catch (e) {
            poll(POLL_BACKOFF_MS)
        }
    }, delay)
}

const buildSubmitParams = (tpl: PlayTemplate): Record<string, any> | null => {
    const params: Record<string, any> = {
        play: curPlay.value,
        consent: 1,
        template: {
            ref_id: tpl.ref_id,
            video_url: tpl.video_url,
            name: tpl.name,
            cover_url: tpl.cover_url || '',
            duration_sec: tpl.duration_sec || 0,
            face_count: tpl.face_count || 1,
            allow_bg_replace: !!tpl.allow_bg_replace
        }
    }
    if (curPlay.value === 'clip') {
        if (!clipVideo.uri) {
            feedback.msgWarning('请先上传你的视频')
            return null
        }
        const hook = hookOn.value ? hookText.value.trim() : ''
        if (hookOn.value && !hook) {
            feedback.msgWarning('钩子文案开着但还没填，写一句或关掉开关')
            return null
        }
        params.product_video_url = clipVideo.uri
        params.product_video_sec = clipVideo.duration
        params.clip_keep_sec = keepSec.value
        params.transition = transition.value
        params.splice_sfx = sfxOn.value ? 1 : 0
        params.hook_text = hook
        return params
    }
    for (const slot of faceSlots.value) {
        if (faces[slot.key].fileId <= 0) {
            feedback.msgWarning(
                slot.label
                    ? `请先上传${slot.label}的正脸照`
                    : '请先上传你的正脸照'
            )
            return null
        }
    }
    if (!consent.value) {
        feedback.msgWarning('请先勾选「本人授权」')
        return null
    }
    params.face_file_id = face.fileId
    if (faceSlots.value.length > 1) params.face_file_id2 = face2.fileId
    if (curPlay.value === 'face') {
        const clean = lines.value.map((l) => ({
            role: l.role,
            text: (l.text || '').trim()
        }))
        if (clean.length === 0 || clean.some((l) => !l.text)) {
            feedback.msgWarning('台词不能有空句')
            return null
        }
        if (speechOver.value) {
            feedback.msgWarning(
                `台词预估口播约 ${speechSeconds.value} 秒，超过 ${MAX_SPEECH_SECONDS} 秒上限，请精简`
            )
            return null
        }
        params.lines = clean
        params.voice = voice.value
        return params
    }
    const mode = tpl.allow_bg_replace ? bgMode.value : 'keep'
    if (mode === 'upload' && bg.fileId <= 0) {
        feedback.msgWarning('请上传背景图')
        return null
    }
    const speech = speechOn.value ? speechText.value.trim() : ''
    if (speechOn.value && !speech) {
        feedback.msgWarning('口播开着但还没填，写一句或关掉开关')
        return null
    }
    if (speech && speech.length < MIN_SPEECH_RUNES) {
        feedback.msgWarning(`口播至少 ${MIN_SPEECH_RUNES} 个字，太短无法对口型`)
        return null
    }
    params.bg_mode = mode
    if (mode === 'upload') params.bg_file_id = bg.fileId
    params.speech_text = speech
    if (speech) params.voice = voice.value
    return params
}

const handleGenerate = async (retry: boolean) => {
    const tpl = picked.value
    if (!tpl) {
        feedback.msgWarning('请先选择模板')
        return
    }
    const params = buildSubmitParams(tpl)
    if (!params) return

    // 站长与中台都按 sn 幂等：上一单已出终态（成功/失败）再沿用同一 sn，
    // 拿回来的只会是那条终态任务本身——失败浮层的「重试」曾经因此等于没点
    if (!retry || gen.phase !== 'running') currentSn = ''
    if (!currentSn)
        currentSn = `pv${Date.now()}${Math.random().toString(36).slice(2, 8)}`
    params.sn = currentSn

    gen.show = true
    gen.phase = 'running'
    gen.stepLabel = '排队等待生成'
    gen.percent = 6
    gen.elapsed = 0
    gen.videoUrl = ''
    gen.failReason = ''
    try {
        const data: any = await submitPlayVideo(params)
        gen.sn = data.sn
        gen.tokensCost = Number(data.tokens_cost || 0)
        stopTimers()
        const expect = EXPECT_SECONDS[curPlay.value]
        timer = setInterval(() => {
            gen.elapsed += 1
            gen.percent = Math.min(
                PERCENT_CAP,
                6 + (gen.elapsed / expect) * (PERCENT_CAP - 6)
            )
        }, 1000)
        poll(POLL_INTERVAL_MS)
    } catch (e: any) {
        gen.show = false
        stopTimers()
        const msg = String(e?.msg || e?.message || '提交失败')
        feedback.msgError(msg)
        // 模板被后台下架：清掉选中并重拉模板墙，下架的那条会消失、自动选到第一条可用的
        if (msg.includes(TEMPLATE_OFFLINE_HINT)) {
            picked.value = null
            loadTemplates()
        }
    }
}
const { lockFn: lockGenerate, isLock: submitting } = useLockFn(handleGenerate)

onMounted(loadTemplates)
onUnmounted(stopTimers)
</script>

<style lang="scss" scoped>
.pv-line-changed :deep(.el-textarea__inner) {
    box-shadow: 0 0 0 1px #93c5fd inset;
}
.section-title {
    @apply text-sm font-bold text-[#1D2129] mb-3;
}
.tpl-cover {
    aspect-ratio: 9 / 16;
    background: linear-gradient(160deg, #2b3a5c, #151c2e);
}
.clip-bar {
    background: linear-gradient(90deg, #2b3a5c, #3d4a63);
}
</style>
