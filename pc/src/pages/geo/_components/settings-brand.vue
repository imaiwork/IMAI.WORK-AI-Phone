<template>
    <div class="mx-auto w-full max-w-[880px]">
        <div class="bg-white rounded-xl border border-br overflow-hidden">
            <div class="px-6 py-5 border-b border-[#F1F5F9] flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <div class="text-sm font-semibold text-slate-900">品牌画像</div>
                    <div class="text-xs text-slate-400 mt-0.5">监测命中和内容生成都读这份资料，别名越全越准</div>
                </div>
                <span v-if="info?.update_time" class="text-xs text-slate-400 shrink-0 tabular-nums">最近更新 {{ fmtTime(info.update_time) }}</span>
            </div>

            <ElForm label-position="top" class="brand-form px-6 py-5">
                <section>
                    <div class="text-sm font-semibold text-slate-900">怎么被认出来</div>
                    <div class="text-xs text-slate-400 mt-0.5 mb-4">AI 回答里出现品牌名或任一别名，就算在线</div>
                    <div class="grid grid-cols-2 gap-x-4">
                        <ElFormItem label="品牌 / 公司名称" required>
                            <ElInput v-model="form.brand_name" placeholder="对外使用的品牌名" />
                        </ElFormItem>
                        <ElFormItem label="所属行业">
                            <ElInput v-model="form.industry" placeholder="如 光伏运维 / 软件工具" />
                        </ElFormItem>
                    </div>
                    <ElFormItem label="官网">
                        <ElInput v-model="form.website" placeholder="https://" />
                    </ElFormItem>
                    <ElFormItem label="品牌 Logo">
                        <div class="flex items-center gap-3">
                            <div class="w-20 h-20 rounded-xl border border-br overflow-hidden bg-slate-50 shrink-0">
                                <img v-if="form.logo" :src="form.logo" alt="品牌 Logo" class="w-full h-full object-cover" />
                                <span v-else class="h-full grid place-items-center text-xs text-slate-400">未上传</span>
                            </div>
                            <div>
                                <input ref="logoInput" type="file" accept="image/png,image/jpeg,image/webp" class="hidden" @change="onLogoPicked" />
                                <div class="flex items-center gap-2">
                                    <ElButton class="!h-8 !rounded-lg" :loading="logoUploading" @click="logoInput?.click()">上传 Logo</ElButton>
                                    <ElButton v-if="form.logo" link type="danger" @click="form.logo = ''">移除</ElButton>
                                </div>
                                <div class="text-xs text-slate-500 mt-1">公众号投稿会用作封面，建议正方形图片</div>
                            </div>
                        </div>
                    </ElFormItem>
                    <ElFormItem label="品牌别名">
                        <div class="chip-well">
                            <ElTag v-for="(a, i) in form.aliases" :key="a" closable size="large" effect="light" @close="form.aliases.splice(i, 1)">{{ a }}</ElTag>
                            <ElInput v-model="aliasInput" class="!w-[160px]" size="small" placeholder="回车添加" @keyup.enter="addAlias" />
                        </div>
                        <div class="text-slate-400 text-xs mt-1">简称、英文名、旧名都加上，避免漏检</div>
                    </ElFormItem>
                </section>

                <section class="mt-2 pt-6 border-t border-[#F1F5F9]">
                    <div class="text-sm font-semibold text-slate-900">产品怎么讲</div>
                    <div class="text-xs text-slate-400 mt-0.5 mb-4">生成文章时会引用这些描述</div>
                    <ElFormItem label="产品介绍">
                        <ElInput v-model="form.intro" type="textarea" :rows="3" placeholder="用一两段话说清你是谁、解决什么问题" />
                    </ElFormItem>
                    <div class="grid grid-cols-2 gap-x-4">
                        <ElFormItem label="产品特点">
                            <ElInput v-model="form.features" type="textarea" :rows="3" placeholder="核心卖点，逗号分隔" />
                        </ElFormItem>
                        <ElFormItem label="目标客户">
                            <ElInput v-model="form.target_customer" type="textarea" :rows="3" placeholder="如 中小企业主 / 电站业主" />
                        </ElFormItem>
                    </div>
                </section>

                <section class="mt-2 pt-6 border-t border-[#F1F5F9]">
                    <div class="text-sm font-semibold text-slate-900">和谁比</div>
                    <div class="text-xs text-slate-400 mt-0.5 mb-4">监测里会按这份名单对比提及</div>
                    <ElFormItem label="竞品品牌">
                        <div class="chip-well">
                            <ElTag v-for="(c, i) in form.competitors" :key="c" closable size="large" effect="light" @close="form.competitors.splice(i, 1)">{{ c }}</ElTag>
                            <ElInput v-model="compInput" class="!w-[160px]" size="small" placeholder="回车添加" @keyup.enter="addComp" />
                        </div>
                    </ElFormItem>
                </section>
            </ElForm>

            <div class="px-6 py-4 border-t border-[#F1F5F9] flex items-center justify-end">
                <ElButton type="primary" class="!h-11 !rounded-xl !px-6" :loading="saving" @click="save">保存画像</ElButton>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { ElMessage } from 'element-plus'
import { geoProjectUpdate } from '@/api/geo'
import { uploadImage } from '@/api/app'
import { fmtGeoTime as fmtTime } from '../_composables/geo-time'

const props = defineProps<{ pid: number; info: any }>()
const emit = defineEmits(['saved'])
const errText = (e: any) => (typeof e === 'string' ? e : e?.msg || '操作失败')

const saving = ref(false)
const aliasInput = ref('')
const compInput = ref('')
const form = reactive<any>({ brand_name: '', industry: '', website: '', logo: '', intro: '', features: '', target_customer: '', aliases: [], competitors: [] })
const logoInput = ref<HTMLInputElement>()
const logoUploading = ref(false)
const onLogoPicked = async (ev: Event) => {
    const input = ev.target as HTMLInputElement
    const file = input.files?.[0]
    input.value = ''
    if (!file) return
    logoUploading.value = true
    try {
        const res: any = await uploadImage({ file })
        if (res?.uri) form.logo = res.uri
        else ElMessage.error('上传未返回图片地址')
    } catch (e) { ElMessage.error(errText(e)) } finally { logoUploading.value = false }
}

const addAlias = () => {
    const v = aliasInput.value.trim()
    if (!v) return
    if (form.aliases.includes(v)) return ElMessage.warning('别名不可重复')
    form.aliases.push(v); aliasInput.value = ''
}
const addComp = () => {
    const v = compInput.value.trim()
    if (!v) return
    if (form.competitors.includes(v)) return ElMessage.warning('竞品不可重复')
    form.competitors.push(v); compInput.value = ''
}

const save = async () => {
    if (!form.brand_name.trim()) return ElMessage.warning('请填写品牌名称')
    saving.value = true
    try {
        await geoProjectUpdate({ id: props.pid, ...form })
        ElMessage.success('已保存')
        emit('saved')
    } catch (e) { ElMessage.error(errText(e)) } finally { saving.value = false }
}

watch(() => props.info, (v) => {
    if (!v) return
    Object.assign(form, {
        brand_name: v.brand_name || '', industry: v.industry || '', website: v.website || '', logo: v.logo || '',
        intro: v.intro || '', features: v.features || '', target_customer: v.target_customer || '',
        aliases: [...(v.aliases || [])], competitors: [...(v.competitors || [])],
    })
}, { immediate: true })
</script>

<style lang="scss" scoped>
.brand-form {
    :deep(.el-form-item__label) {
        font-weight: 500;
        color: #0f172a;
    }
}
.chip-well {
    @apply flex flex-wrap items-center gap-2 w-full min-h-[52px] rounded-xl border border-br bg-slate-50 px-3 py-2;
}
</style>
