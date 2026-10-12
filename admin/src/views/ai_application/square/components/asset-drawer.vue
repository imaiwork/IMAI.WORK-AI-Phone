<template>
    <el-drawer
        v-model="visible"
        :title="isEdit ? '编辑素材' : '添加素材'"
        size="1040px"
        destroy-on-close
        class="square-asset-drawer"
        @close="handleClose">
        <div class="flex gap-5 min-h-0 h-full">
            <div class="flex-1 min-w-0 space-y-5 overflow-y-auto pr-1">
                <section>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="step-n">1</span>
                        <span class="text-sm font-semibold text-[#1D2129]">放进哪个分组 <span class="text-danger">*</span></span>
                    </div>
                    <p class="text-xs text-[#86909C] mb-3">客户在广场里按分组筛选，分组名就是他看到的筛选标签</p>
                    <div class="flex flex-wrap gap-2">
                        <el-check-tag
                            v-for="item in groups"
                            :key="item.id"
                            :checked="form.group_id === item.id"
                            @change="form.group_id = item.id">
                            {{ item.name }}
                        </el-check-tag>
                        <el-button size="small" @click="showInlineGroup = true">+ 新建分组</el-button>
                    </div>
                    <div v-if="showInlineGroup" class="flex items-center gap-2 mt-3">
                        <el-input
                            v-model="inlineGroupName"
                            maxlength="16"
                            placeholder="新分组的名字，例：装修 · 施工现场" />
                        <el-button type="primary" @click="handleCreateGroup">确定</el-button>
                        <el-button @click="showInlineGroup = false">取消</el-button>
                    </div>
                    <p class="mt-2 text-xs text-[#86909C]">新建的分组默认上架；备注、排序以后在左侧「编辑分组」里改</p>
                </section>

                <section>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="step-n">2</span>
                        <span class="text-sm font-semibold text-[#1D2129]">
                            {{ isEdit ? '文件（换文件引用关系不变）' : '上传文件' }}
                            <span class="text-danger">*</span>
                        </span>
                    </div>
                    <p class="text-xs text-[#86909C] mb-3">
                        {{
                            isEdit
                                ? '编辑时只能换当前这一份文件，不会新增引用'
                                : '可一次多选视频 / 图片，类型和时长自动识别，名称默认取文件名；视频和图片会按合成规范转码'
                        }}
                    </p>
                    <div
                        class="border border-dashed border-[#C0C4CC] rounded-lg px-4 py-6 text-center cursor-pointer bg-[#FAFBFC]"
                        @click="triggerPick"
                        @dragover.prevent
                        @drop.prevent="handleDrop">
                        <div class="text-sm text-[#303133]">拖拽文件到这里，或点击选择</div>
                        <div class="text-xs text-[#86909C] mt-1">支持视频 / 图片，{{ isEdit ? '仅 1 个' : '可多选' }}</div>
                    </div>
                    <input
                        ref="fileInputRef"
                        type="file"
                        class="hidden"
                        accept="video/*,image/*"
                        :multiple="!isEdit"
                        @change="handleFileChange" />
                    <div v-if="rows.length" class="mt-3 space-y-2">
                        <div
                            v-for="row in rows"
                            :key="row.key"
                            class="flex items-center gap-3 rounded-md border border-[#EBEEF5] px-3 py-2">
                            <el-image
                                v-if="rowCover(row)"
                                :src="rowCover(row)"
                                class="w-10 h-10 rounded object-cover flex-shrink-0" />
                            <div v-else class="w-10 h-10 rounded bg-[#F2F3F5] flex items-center justify-center text-xs text-[#909399]">
                                {{ row.kind === 1 ? '视频' : '图片' }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <el-input v-model="row.name" maxlength="24" size="small" />
                                <div class="text-xs text-[#86909C] mt-1">
                                    {{ row.kind === 1 ? '视频' : '图片' }}
                                    <span v-if="row.duration_sec"> · {{ formatDuration(row.duration_sec) }}</span>
                                    <span v-if="row.status === 'uploading'"> · 上传中 {{ row.progress }}%</span>
                                    <span v-else-if="row.status === 'fail'" class="text-danger"> · 失败</span>
                                </div>
                            </div>
                            <el-button v-if="!isEdit" link type="danger" @click="removeRow(row.key)">移除</el-button>
                        </div>
                    </div>
                </section>

                <section>
                    <div class="flex items-center gap-2 mb-3">
                        <span class="step-n opt">3</span>
                        <span class="text-sm font-semibold text-[#1D2129]">补充信息</span>
                        <span class="text-xs text-[#86909C]">（选填）</span>
                    </div>
                    <el-form label-position="top">
                        <el-form-item label="标签">
                            <el-input v-model="form.tags" placeholder="逗号分隔，例：空镜,竖屏,暖色" maxlength="80" />
                            <p class="mt-1 text-xs text-[#86909C]">最多 6 个，每个最多 10 字</p>
                        </el-form-item>
                        <el-form-item v-if="hasVideo" label="视频封面">
                            <material-picker v-model="coverUrl" :limit="1" />
                            <p class="mt-1 text-xs text-[#86909C]">不传则自动截取视频开头画面作封面</p>
                        </el-form-item>
                    </el-form>
                    <el-button link type="primary" class="!px-0" @click="showMore = !showMore">
                        {{ showMore ? '收起更多设置' : '更多设置' }}
                    </el-button>
                    <div v-if="showMore" class="mt-3 space-y-3">
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-[#303133] w-10">上架</span>
                            <el-switch v-model="form.enabled" :active-value="1" :inactive-value="0" />
                            <span class="text-xs text-[#86909C]">关掉就先存着，客户看不到</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-[#303133] w-10">排序</span>
                            <el-input-number v-model="form.sort" :controls="false" class="!w-[120px]" />
                        </div>
                    </div>
                </section>
            </div>

            <aside class="w-[340px] flex-shrink-0 border-l border-[#EBEEF5] pl-4 overflow-y-auto">
                <div class="flex items-center gap-1 text-sm font-semibold text-[#1D2129] mb-1">客户那边会看到什么</div>
                <p class="text-xs text-[#86909C] mb-4">跟着左边实时变，画的是客户手机上的样子</p>
                <div class="text-xs font-semibold text-[#303133] mb-2">① 客户在「素材库广场」里看到的</div>
                <div class="rounded-lg bg-[#F5F7FA] p-3 mb-4">
                    <div class="flex flex-wrap gap-1 mb-2">
                        <span class="px-2 py-0.5 rounded-full bg-white text-[11px] text-[#409EFF]">全部</span>
                        <span class="px-2 py-0.5 rounded-full bg-white text-[11px] text-[#606266]">
                            {{ currentGroupName || '未选分组' }}
                        </span>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div v-for="card in previewCards" :key="card.key" class="relative rounded overflow-hidden bg-white">
                            <div class="aspect-[3/4] bg-[#EEF0F4]">
                                <img v-if="card.cover" :src="card.cover" class="w-full h-full object-cover" />
                            </div>
                            <div class="absolute top-1 left-1 text-[10px] text-white px-1 rounded bg-black/55">
                                {{ card.kind === 1 ? '视频' : '图片' }}
                            </div>
                            <div class="p-1.5 text-[11px] truncate">{{ card.name }}</div>
                        </div>
                    </div>
                </div>
                <div class="text-xs font-semibold text-[#303133] mb-2">② 加入「画面素材」后</div>
                <div class="rounded-lg bg-[#F5F7FA] p-3 mb-4">
                    <div class="relative w-24 rounded overflow-hidden bg-white">
                        <div class="aspect-[3/4] bg-[#EEF0F4]">
                            <img v-if="firstCard?.cover" :src="firstCard.cover" class="w-full h-full object-cover" />
                        </div>
                        <span class="absolute top-1 left-1 text-[10px] text-white px-1 rounded bg-[#409EFF]">广场</span>
                    </div>
                    <p class="mt-2 text-[11px] text-[#86909C]">排在最前面，左上角标「广场」</p>
                </div>
                <div class="text-xs font-semibold text-[#303133] mb-2">③ 下架或删除之后</div>
                <div class="rounded-lg bg-[#F5F7FA] p-3 mb-4">
                    <div class="relative w-24 rounded overflow-hidden bg-[#DCDFE6] grayscale">
                        <div class="aspect-[3/4] bg-[#C0C4CC] flex items-center justify-center text-[11px] text-[#909399]">
                            已下架
                        </div>
                    </div>
                </div>
                <div class="text-xs font-semibold text-[#303133] mb-2">「引用」是什么意思</div>
                <ul class="text-[11px] text-[#606266] space-y-1 leading-5">
                    <li>不复制文件、不占客户的上传空间</li>
                    <li>你改名称 / 换文件 / 换封面，所有加过的客户跟着变</li>
                    <li>你下架或删除，客户那边变灰显示「已下架」</li>
                    <li>客户自己移除只影响他自己</li>
                </ul>
                <p class="mt-3 text-[11px] text-[#909399]">
                    客户加入后会出现在人设素材库。AI 员工选「素材库+广场」时，自有素材与广场素材对半使用，不够再用另一边补缺口，不用客户先加入。
                </p>
            </aside>
        </div>
        <template #footer>
            <div class="flex items-center gap-2">
                <span class="mr-auto text-xs text-[#86909C]">{{ summaryText }}</span>
                <span v-if="disableReason" class="text-xs text-[#E6A23C]">{{ disableReason }}</span>
                <el-button @click="visible = false">取消</el-button>
                <el-button type="primary" :disabled="!!disableReason" :loading="saving" @click="handleSave">
                    上架给客户
                </el-button>
            </div>
        </template>
    </el-drawer>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import feedback from '@/utils/feedback'
import { uploadFile } from '@/api/app'
import { addSquareAsset, addSquareGroup, updateSquareAsset } from '@/api/ai_application/square'
import type { SquareGroupRow } from './group-drawer.vue'

interface AssetRow {
    id?: number
    group_id: number
    kind: number
    name: string
    url: string
    cover_url: string
    duration_sec: number
    tags: string[]
    enabled: number
    sort: number
}

interface UploadRow {
    key: string
    name: string
    kind: number
    url: string
    cover_url: string
    duration_sec: number
    status: 'uploading' | 'done' | 'fail'
    progress: number
}

const props = defineProps<{
    modelValue: boolean
    groups: SquareGroupRow[]
    asset: AssetRow | null
    defaultGroupId: number
}>()

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
    (e: 'saved'): void
    (e: 'group-created'): void
}>()

const visible = computed({
    get: () => props.modelValue,
    set: (value: boolean) => emit('update:modelValue', value)
})

const isEdit = computed(() => !!props.asset?.id)
const fileInputRef = ref<HTMLInputElement>()
const showInlineGroup = ref(false)
const inlineGroupName = ref('')
const showMore = ref(false)
const saving = ref(false)
const coverUrl = ref('')
const rows = ref<UploadRow[]>([])
const form = reactive({
    group_id: 0,
    tags: '',
    enabled: 1,
    sort: 0
})

const currentGroupName = computed(
    () => props.groups.find((item) => item.id === form.group_id)?.name || ''
)
const hasVideo = computed(() => rows.value.some((row) => row.kind === 1))
const firstCard = computed(() => previewCards.value[0])
const previewCards = computed(() =>
    rows.value
        .filter((row) => row.status === 'done')
        .map((row) => ({
            key: row.key,
            name: row.name || '未命名素材',
            kind: row.kind,
            cover: rowCover(row)
        }))
)
const uploading = computed(() => rows.value.some((row) => row.status === 'uploading'))
const readyRows = computed(() => rows.value.filter((row) => row.status === 'done' && row.url))
const summaryText = computed(() => {
    if (!readyRows.value.length) return '还没有可上架的文件'
    return `将上架 ${readyRows.value.length} 个素材到「${currentGroupName.value || '未选分组'}」`
})
const disableReason = computed(() => {
    if (!form.group_id) return '请先选择分组'
    if (uploading.value) return '文件还在上传'
    if (!readyRows.value.length) return '请先上传文件'
    return ''
})

watch(
    () => props.modelValue,
    (open) => {
        if (!open) return
        showInlineGroup.value = false
        inlineGroupName.value = ''
        showMore.value = false
        form.group_id = props.asset?.group_id || props.defaultGroupId || 0
        form.tags = (props.asset?.tags || []).join(',')
        form.enabled = props.asset ? Number(props.asset.enabled) : 1
        form.sort = props.asset?.sort || 0
        coverUrl.value = props.asset?.cover_url || ''
        rows.value = props.asset
            ? [
                  {
                      key: `edit-${props.asset.id}`,
                      name: props.asset.name,
                      kind: props.asset.kind,
                      url: props.asset.url,
                      cover_url: props.asset.cover_url,
                      duration_sec: props.asset.duration_sec,
                      status: 'done',
                      progress: 100
                  }
              ]
            : []
    }
)

const handleClose = () => emit('update:modelValue', false)
const triggerPick = () => fileInputRef.value?.click()

const handleCreateGroup = async () => {
    const name = inlineGroupName.value.trim()
    if (!name) {
        feedback.msgWarning('分组名称不能为空')
        return
    }
    const created: any = await addSquareGroup({ name, enabled: 1, sort: 0 })
    form.group_id = Number(created?.id || 0)
    inlineGroupName.value = ''
    showInlineGroup.value = false
    emit('group-created')
}

const handleFileChange = (event: Event) => {
    const input = event.target as HTMLInputElement
    const files = Array.from(input.files || [])
    input.value = ''
    files.forEach((file) => uploadOne(file))
}

const handleDrop = (event: DragEvent) => {
    const files = Array.from(event.dataTransfer?.files || [])
    files.forEach((file) => uploadOne(file))
}

const removeRow = (key: string) => {
    rows.value = rows.value.filter((row) => row.key !== key)
}

const patchRow = (key: string, patch: Partial<UploadRow>) => {
    const index = rows.value.findIndex((item) => item.key === key)
    if (index < 0) return
    rows.value[index] = { ...rows.value[index], ...patch }
}

const uploadOne = async (file: File) => {
    if (isEdit.value) {
        rows.value = []
    }
    const kind = file.type.startsWith('image/') ? 2 : 1
    const key = `${file.name}-${file.size}-${Date.now()}-${Math.random()}`
    rows.value = [
        ...rows.value,
        {
            key,
            name: file.name.replace(/\.[^.]+$/, '').slice(0, 24),
            kind,
            url: '',
            cover_url: '',
            duration_sec: 0,
            status: 'uploading',
            progress: 0
        }
    ]
    try {
        let durationSec = 0
        if (kind === 1) {
            durationSec = await readVideoDuration(file)
            patchRow(key, { duration_sec: durationSec })
        }
        const type = kind === 1 ? 'video' : 'image'
        const res: any = await uploadFile(
            type,
            {
                file,
                data:
                    kind === 1
                        ? { generate_thumbnail: 1, fetch_video_info: 1, ffmpeg: 1 }
                        : { ffmpeg: 1 }
            },
            (evt: any) => {
                const total = Number(evt?.total || 0)
                patchRow(key, {
                    progress: total ? Math.min(99, Math.round((Number(evt.loaded || 0) / total) * 100)) : 10
                })
            }
        )
        const url = res?.uri || res?.url || ''
        const autoCover = kind === 1 ? String(res?.thumbnail_path || res?.thumbnail_url || '') : url
        patchRow(key, {
            url,
            cover_url: autoCover,
            duration_sec: Number(res?.duration || durationSec || 0),
            status: url ? 'done' : 'fail',
            progress: 100
        })
    } catch {
        patchRow(key, { status: 'fail' })
    }
}

const handleSave = async () => {
    if (disableReason.value) return
    saving.value = true
    try {
        const tags = form.tags
        const cover = coverUrl.value
        if (isEdit.value && props.asset) {
            const row = readyRows.value[0]
            await updateSquareAsset({
                id: props.asset.id,
                group_id: form.group_id,
                kind: row.kind,
                name: row.name,
                url: row.url,
                cover_url: cover || row.cover_url,
                duration_sec: row.duration_sec,
                tags,
                enabled: form.enabled,
                sort: form.sort
            })
        } else {
            await addSquareAsset({
                group_id: form.group_id,
                items: readyRows.value.map((row) => ({
                    group_id: form.group_id,
                    kind: row.kind,
                    name: row.name,
                    url: row.url,
                    cover_url: row.kind === 1 ? cover || row.cover_url : '',
                    duration_sec: row.duration_sec,
                    tags,
                    enabled: form.enabled,
                    sort: form.sort
                }))
            })
        }
        feedback.msgSuccess('已上架')
        emit('saved')
        visible.value = false
    } catch (error: any) {
        feedback.msgError(error || '保存失败')
    } finally {
        saving.value = false
    }
}

const rowCover = (row: UploadRow) =>
    row.kind === 2 ? row.url : coverUrl.value || row.cover_url || ''

const formatDuration = (sec: number) => {
    const value = Math.max(0, Math.floor(sec))
    const m = Math.floor(value / 60)
    const s = value % 60
    return `${m}:${String(s).padStart(2, '0')}`
}

const readVideoDuration = (file: File) =>
    new Promise<number>((resolve) => {
        const url = URL.createObjectURL(file)
        const video = document.createElement('video')
        video.preload = 'metadata'
        video.onloadedmetadata = () => {
            const sec = Math.round(video.duration || 0)
            URL.revokeObjectURL(url)
            resolve(Number.isFinite(sec) ? sec : 0)
        }
        video.onerror = () => {
            URL.revokeObjectURL(url)
            resolve(0)
        }
        video.src = url
    })
</script>

<style lang="scss" scoped>
.step-n {
    @apply inline-flex items-center justify-center w-5 h-5 rounded-full bg-primary text-white text-xs font-bold;
    &.opt {
        @apply bg-[#C0C4CC];
    }
}
</style>
