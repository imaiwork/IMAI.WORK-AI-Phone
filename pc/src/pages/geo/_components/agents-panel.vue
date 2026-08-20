<template>
    <div class="space-y-4" v-spin="{ show: contentLoading, text: '加载中...' }">
        <div>
            <div class="text-sm font-semibold text-slate-900">GEO 助手</div>
            <div class="text-slate-500 text-sm mt-0.5">用监测结果驱动内容生产：写文章、出报告、转短视频</div>
        </div>
        <div class="grid grid-cols-3 gap-5">
            <div class="agent-card" @click="$emit('go', 'generate')">
                <div class="w-12 h-12 rounded-xl bg-primary/10 text-primary grid place-items-center">
                    <Icon name="el-icon-EditPen" :size="22" />
                </div>
                <div class="font-semibold text-slate-800 mt-4">撰写文章</div>
                <div class="text-sm text-slate-500 mt-1 min-h-[40px]">按话题和场景问题生成品牌内容</div>
                <div class="mt-auto pt-4 flex items-center justify-between gap-3">
                    <span class="text-xs text-slate-400">按模型用量计费</span>
                    <ElButton type="primary" class="!h-11 !rounded-xl" @click.stop="$emit('go', 'generate')">开始创作</ElButton>
                </div>
            </div>
            <div class="agent-card" @click="openReport">
                <div class="w-12 h-12 rounded-xl bg-primary/10 text-primary grid place-items-center">
                    <Icon name="el-icon-DataAnalysis" :size="22" />
                </div>
                <div class="font-semibold text-slate-800 mt-4">GEO 诊断报告</div>
                <div class="text-sm text-slate-500 mt-1 min-h-[40px]">
                    整合可见度、竞争与引用，给出优化建议
                    <div v-if="latest?.exists" class="text-xs text-slate-400 mt-1">最近生成：{{ latest.generated_at }}</div>
                </div>
                <div class="mt-auto pt-4 flex items-center justify-between gap-3">
                    <span class="text-xs text-slate-400">{{ reportPriceText }}</span>
                    <ElButton type="primary" class="!h-11 !rounded-xl" :plain="!latest?.exists" :loading="reportLoading" @click.stop="openReport">
                        {{ latest?.exists ? '查看报告' : '生成报告' }}
                    </ElButton>
                </div>
            </div>
            <div class="agent-card" @click="openScript">
                <div class="w-12 h-12 rounded-xl bg-primary/10 text-primary grid place-items-center">
                    <Icon name="el-icon-VideoCamera" :size="22" />
                </div>
                <div class="font-semibold text-slate-800 mt-4">转口播稿做视频</div>
                <div class="text-sm text-slate-500 mt-1 min-h-[40px]">把 GEO 文章转成口播稿,带着文案跳到「数字人纯口播视频」直接做片</div>
                <div class="mt-auto pt-4 flex items-center justify-between gap-3">
                    <span class="text-xs text-slate-400">按数字人文案生成计费</span>
                    <ElButton class="!h-11 !rounded-xl" @click.stop="openScript">✦ 转口播稿</ElButton>
                </div>
            </div>
        </div>

        <!-- 文章转口播稿 → 数字人纯口播视频 -->
        <GeoDialog
            v-model="showScript"
            layout="panel"
            title="转口播稿做视频"
            desc="口播稿由数字人模块的文案生成能力产出,按其单价计费;生成后可直接改"
            width="680px">
            <div class="flex items-center gap-3">
                <ElSelect v-model="scriptContentId" placeholder="选择一篇 GEO 文章" filterable style="flex:1" @change="scriptText = ''">
                    <ElOption v-for="c in scriptContents" :key="c.id" :label="c.title" :value="c.id" />
                </ElSelect>
                <ElSelect v-model="scriptLength" style="width:120px">
                    <ElOption label="约 200 字" :value="200" /><ElOption label="约 300 字" :value="300" /><ElOption label="约 500 字" :value="500" />
                </ElSelect>
                <ElButton type="primary" class="!rounded-xl" :loading="scriptLoading" :disabled="!scriptContentId" @click="genScript">
                    {{ scriptText ? '重新生成' : '生成口播稿' }}
                </ElButton>
            </div>
            <ElInput v-model="scriptText" type="textarea" :rows="10" class="mt-4"
                placeholder="选一篇文章,点「生成口播稿」;也可以直接把口播稿粘进来" />
            <div class="text-right text-slate-400 text-xs mt-1">{{ scriptText.length }} 字</div>
            <div class="flex justify-end gap-2 mt-4">
                <ElButton @click="showScript = false">取消</ElButton>
                <ElButton type="primary" :disabled="!scriptText.trim()" @click="toDigitalHuman">带入数字人制作视频</ElButton>
            </div>
        </GeoDialog>

        <GeoDialog
            v-model="showReport"
            layout="panel"
            title="GEO 诊断报告"
            :desc="latest?.generated_at ? `生成时间 ${latest.generated_at}` : '查看已生成的诊断报告'"
            width="900px">
            <div v-if="report" ref="reportRef" class="geo-report-print space-y-6 max-h-[72vh] overflow-y-auto pr-2">
                <!-- 报告抬头 -->
                <div class="rounded-2xl p-5 text-white" style="background:linear-gradient(135deg,#0065fb,#4f9dff)">
                    <div class="text-xl font-semibold">{{ report.brand }} × IMAI GEO 诊断报告</div>
                    <div class="text-xs opacity-90 mt-2">
                        报告日期:{{ report.date }} · 服务对象:{{ report.brand }} · 监测范围:话题 {{ report.meta.topic_names?.length || 0 }} 个
                        <span v-if="report.meta.topic_names?.length">({{ report.meta.topic_names.join('、') }})</span>
                    </div>
                    <div v-if="latest?.generated_at" class="text-xs opacity-75 mt-1">生成时间:{{ latest.generated_at }}</div>
                </div>

                <!-- 1. 报告说明 + 数据概况 -->
                <div>
                    <div class="rep-t">1. 报告说明</div>
                    <div class="text-slate-500 text-sm mb-3">
                        本次GEO诊断报告基于 <b class="text-slate-900">{{ report.brand }}</b> 在 {{ report.meta.engine_count }} 个主流AI搜索平台({{ (report.meta.engine_labels || []).join('、') }})的表现数据,通过
                        {{ report.meta.question_count }} 个场景问题的采集,获得 {{ report.meta.cell_total }} 条AI问答数据,全面分析品牌在AI搜索生态中的品牌表现和竞争地位。
                    </div>
                    <div class="grid grid-cols-5 gap-3 mb-3">
                        <div class="rep-card"><div class="rep-num">{{ report.meta.cell_total }}</div><div class="rep-lab">问答数据</div></div>
                        <div class="rep-card"><div class="rep-num">{{ report.meta.question_count }}</div><div class="rep-lab">场景问题</div></div>
                        <div class="rep-card"><div class="rep-num">{{ report.meta.engine_count }}</div><div class="rep-lab">AI平台</div></div>
                        <div class="rep-card"><div class="rep-num">{{ report.meta.competitor_count }}</div><div class="rep-lab">竞品品牌</div></div>
                        <div class="rep-card"><div class="rep-num">{{ report.meta.source_count }}</div><div class="rep-lab">信源数量</div></div>
                    </div>
                    <div class="rounded-xl bg-[#f8fafc] p-3 text-xs text-slate-500 space-y-1">
                        <div><b class="text-slate-700">品牌可见度</b>:品牌及其别名在AI平台问答中出现的概率 = 品牌在线的场景问题数 / 场景问题总数 × 100%</div>
                        <div><b class="text-slate-700">平均位置</b>:品牌出现时在答案中的平均排位;<b class="text-slate-700">首推/前3/前5占比</b>:出现时排名第1/≤3/≤5的比例</div>
                        <div><b class="text-slate-700">情绪指数</b>:AI回答提及品牌时,正面/中立/负面观点数占总观点数的比例</div>
                    </div>
                </div>

                <!-- 2. AI 诊断概览 -->
                <div>
                    <div class="rep-t">2. AI 诊断概览</div>
                    <div class="text-slate-500 text-sm mb-3">本报告基于 {{ report.date }} 的实时数据,对 <b class="text-slate-900">{{ report.brand }}</b> 在各大主流AI平台的可见度及舆情健康进行了全面诊断。</div>
                    <div class="grid grid-cols-5 gap-3 mb-3">
                        <div class="rep-card"><div class="rep-num text-primary">{{ report.cards.visibility }}%</div><div class="rep-lab">整体可见度</div></div>
                        <div class="rep-card"><div class="rep-num">{{ report.self_rank || '–' }}</div><div class="rep-lab">行业排名</div></div>
                        <div class="rep-card"><div class="rep-num">{{ report.cards.top1_rate }}%</div><div class="rep-lab">首推占比</div></div>
                        <div class="rep-card"><div class="rep-num">{{ report.cards.top3_rate }}%</div><div class="rep-lab">前3占比</div></div>
                        <div class="rep-card"><div class="rep-num">{{ report.cards.top5_rate }}%</div><div class="rep-lab">前5占比</div></div>
                    </div>
                    <div v-if="report.overview" class="text-sm text-slate-600 space-y-2">
                        <div><b class="text-slate-800">整体可见度与行业定位:</b>{{ report.overview.position }}</div>
                        <div><b class="text-slate-800">平台表现分化:</b>{{ report.overview.engines }}</div>
                        <div><b class="text-slate-800">场景覆盖能力:</b>{{ report.overview.scene }}</div>
                    </div>
                </div>

                <!-- 3. AI 可见度 -->
                <div>
                    <div class="rep-t">3. AI 可见度</div>
                    <div class="grid grid-cols-2 gap-4 mb-3">
                        <div>
                            <div class="text-slate-700 text-sm font-bold mb-2">各AI平台的表现</div>
                            <ElTable :data="report.engine_dim || []" size="small">
                                <ElTableColumn label="AI平台" prop="label" min-width="100" />
                                <ElTableColumn label="可见度" width="90"><template #default="{ row }">{{ row.visibility }}%</template></ElTableColumn>
                                <ElTableColumn label="竞品排名" width="90"><template #default="{ row }">{{ fmtRank(row.rank) }}</template></ElTableColumn>
                            </ElTable>
                        </div>
                        <div>
                            <div class="text-slate-700 text-sm font-bold mb-2">品牌竞争排名(TOP10)</div>
                            <ElTable :data="(report.competitors || []).slice(0, 10)" size="small">
                                <ElTableColumn label="排名" width="70"><template #default="{ row }">{{ fmtRankNum(row.rank) }}</template></ElTableColumn>
                                <ElTableColumn label="品牌" min-width="120"><template #default="{ row }"><span :class="{ 'text-primary font-bold': row.is_self }">{{ row.brand }}{{ row.is_self ? '(本品牌)' : '' }}</span></template></ElTableColumn>
                                <ElTableColumn label="可见度" width="90"><template #default="{ row }">{{ row.visibility }}%</template></ElTableColumn>
                            </ElTable>
                        </div>
                    </div>
                    <div class="text-slate-700 text-sm font-bold mb-2">分话题可见度表现</div>
                    <div v-for="t in report.topics" :key="t.topic_id" class="border border-br rounded-xl p-4 mb-3">
                        <div class="flex items-center justify-between mb-2">
                            <b class="text-slate-800">话题:{{ t.topic_name }}</b>
                            <span class="text-sm text-slate-500">总可见度 <b class="text-primary">{{ t.visibility }}%</b> · 竞品排名 <template v-if="t.rank > 0"><b>{{ t.rank }}</b>/{{ t.competitor_total }}</template><template v-else>未上榜</template></span>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <span v-for="e in t.by_engine" :key="e.engine" class="rep-tag rep-tag--plain">{{ e.label }}:{{ e.visibility }}% · {{ fmtRank(e.rank) }}</span>
                        </div>
                    </div>
                    <div v-if="report.best_topic" class="rounded-xl bg-[#f0f7ff] p-3 text-sm text-slate-600">
                        <b class="text-slate-800">最佳话题:</b>{{ report.best_topic.topic_name }}(可见度 {{ report.best_topic.visibility }}% · 竞品排名 {{ fmtRankNum(report.best_topic.rank) }})
                    </div>
                </div>

                <!-- 4. 竞争分析 -->
                <div>
                    <div class="rep-t">4. 竞争分析</div>
                    <div class="text-slate-700 text-sm font-bold mb-2">竞争品牌表现(整体 × 分话题)</div>
                    <div class="overflow-x-auto mb-3">
                        <table class="rep-matrix">
                            <thead>
                                <tr>
                                    <th>品牌名称</th><th>品牌可见度</th><th>排名</th>
                                    <template v-for="tn in report.meta.topic_names || []" :key="tn"><th>{{ tn }}-可见度</th><th>{{ tn }}-排名</th></template>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="m in report.matrix || []" :key="m.brand" :class="{ 'is-self': m.is_self }">
                                    <td>{{ m.brand }}{{ m.is_self ? '(本品牌)' : '' }}</td>
                                    <td>{{ m.visibility }}%</td><td>{{ fmtRankNum(m.rank) }}</td>
                                    <template v-for="bt in m.by_topic" :key="bt.topic_id"><td>{{ bt.visibility }}%</td><td>{{ fmtRankNum(bt.rank) }}</td></template>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-slate-700 text-sm font-bold mb-2">分话题竞争分析</div>
                    <div v-for="t in report.topics" :key="'c' + t.topic_id" class="text-sm text-slate-600 mb-2">
                        <b class="text-slate-800">{{ t.topic_name }}:</b>
                        <template v-if="t.top_brands?.length">
                            该话题可见度头部为 <span v-for="(b, i) in t.top_brands" :key="b.brand">{{ b.brand }}({{ b.visibility }}%,排名{{ b.rank }}){{ i < t.top_brands.length - 1 ? '、' : '' }}</span>;
                            本品牌可见度 {{ t.visibility }}%、排名 {{ fmtRankNum(t.rank) }}。
                        </template>
                        <template v-else>该话题暂无竞品数据。</template>
                    </div>
                </div>

                <!-- 5. 引用信源分析 -->
                <div>
                    <div class="rep-t">5. 引用信源分析</div>
                    <div class="text-slate-500 text-sm mb-2">AI模型的知识主要来源于以下高权重站点,这是GEO优化的主战场:</div>
                    <ElTable v-if="report.quotes?.top_sites?.length" :data="report.quotes.top_sites.slice(0, 10)" size="small" class="mb-3">
                        <ElTableColumn label="信源站点" prop="site" min-width="160" />
                        <ElTableColumn label="引用次数" width="90"><template #default="{ row }">{{ row.cite_count }}次</template></ElTableColumn>
                        <ElTableColumn label="被引文章数" width="100"><template #default="{ row }">{{ row.article_count }}</template></ElTableColumn>
                    </ElTable>
                    <div v-else class="text-slate-400 text-sm mb-3">暂无引用信源数据。</div>
                    <template v-if="report.quotes?.top_articles?.length">
                        <div class="text-slate-700 text-sm font-bold mb-2">本次诊断发现,AI 引用了以下热门内容:</div>
                        <div v-for="a in report.quotes.top_articles.slice(0, 8)" :key="a.url" class="text-xs text-slate-500 mb-1 truncate">
                            · {{ a.title }} <span class="text-slate-300">— {{ a.site }}</span>
                        </div>
                    </template>
                </div>

                <!-- 6. 情绪分析 -->
                <div>
                    <div class="rep-t">6. 情绪分析</div>
                    <div v-if="report.sentiment?.total" class="text-slate-600 text-sm">
                        正面 {{ report.sentiment.positive.rate }}% · 中立 {{ report.sentiment.neutral.rate }}% · 负面 {{ report.sentiment.negative.rate }}%(共 {{ report.sentiment.total }} 条提及)
                    </div>
                    <div v-else class="text-slate-400 text-sm">监测周期内品牌可见度为 0,AI 生态无任何公开提及内容,暂无法开展常规舆情分析(情感倾向、话题分布、传播路径等)。</div>
                </div>

                <!-- 7. 优化策略建议 -->
                <div>
                    <div class="rep-t">7. GEO 内容布局和优化策略建议</div>

                    <!-- 7.1 分平台运营策略：标杆 / 潜力 / 薄弱 -->
                    <template v-if="report.strategy?.platform?.length">
                        <div class="text-slate-700 text-sm font-bold mb-2">7.1 分平台运营策略</div>
                        <div v-for="(p, i) in report.strategy.platform" :key="'p' + i" class="border border-br rounded-xl p-3 mb-2">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="rep-tag" :class="'rep-tag--' + (TIER_TYPE[p.tier] || 'info')">{{ p.tier }}平台</span>
                                <span class="text-slate-800 font-bold text-sm">{{ (p.engines || []).join('、') }}</span>
                            </div>
                            <div class="text-sm text-slate-600">{{ p.action }}</div>
                        </div>
                    </template>

                    <!-- 7.2 全域内容构建策略 -->
                    <template v-if="report.strategy?.content?.length">
                        <div class="text-slate-700 text-sm font-bold mt-4 mb-2">7.2 全域内容构建策略</div>
                        <div v-for="(c, i) in report.strategy.content" :key="'c' + i" class="text-sm text-slate-600 mb-2">
                            <b class="text-slate-800">{{ c.type }}:</b>{{ c.action }}
                        </div>
                    </template>

                    <!-- 7.3 长期运营和检测机制 -->
                    <template v-if="report.strategy?.ops?.length">
                        <div class="text-slate-700 text-sm font-bold mt-4 mb-2">7.3 长期运营和检测机制</div>
                        <div v-for="(o, i) in report.strategy.ops" :key="'o' + i" class="text-sm text-slate-600 mb-2">
                            <b class="text-slate-800">{{ o.title }}:</b>{{ o.action }}
                        </div>
                    </template>

                    <!-- 可直接落地的内容生产建议 -->
                    <template v-if="report.suggestions?.length">
                        <div class="text-slate-700 text-sm font-bold mt-4 mb-2">
                            {{ report.strategy ? '7.4 可落地的内容生产建议' : '优化建议' }}
                        </div>
                        <div v-for="(s, i) in report.suggestions" :key="i" class="text-sm text-slate-600 mb-2">
                            <b class="text-slate-800">{{ i + 1 }}. {{ s.title }}</b>
                            <span v-if="s.content_type" class="rep-tag rep-tag--plain ml-1">{{ s.content_type }}</span>
                            <div>{{ s.desc }}</div>
                        </div>
                    </template>
                    <div v-if="!report.strategy && !report.suggestions?.length" class="text-slate-400 text-sm">暂无优化建议,重新生成报告时将由 AI 基于最新监测结果生成。</div>
                </div>
            </div>
            <template #footer>
                <div class="flex flex-wrap justify-end gap-3">
                    <ElButton class="geo-dialog__btn" @click="showReport = false">关闭</ElButton>
                    <ElButton class="geo-dialog__btn" :loading="reportLoading" @click="regenReport">重新生成</ElButton>
                    <ElButton class="geo-dialog__btn" @click="printReport">打印</ElButton>
                    <ElButton type="primary" class="geo-dialog__btn !font-semibold" :loading="pdfBusy" @click="exportPdf">下载 PDF</ElButton>
                </div>
            </template>
        </GeoDialog>
    </div>
</template>

<script setup lang="ts">
import { ElMessage } from 'element-plus'
import GeoDialog from './geo-dialog.vue'
import { geoConfirm } from '../_composables/geo-confirm'
import { geoReportLatest, geoReportGenerate, geoChargeConfig, geoContents } from '@/api/geo'
import { getCopyWritingGenerate } from '@/api/agent'
import { SidebarTypeEnum } from '@/pages/app/digital_human/_enums'

// 数字人侧边栏「数字人纯口播视频」的 tab 值,跳转时靠它定位页面
const DH_PURE_ORAL_VIDEO = SidebarTypeEnum.DIGITAL_HUMAN_PURE_BOUQUET

const props = defineProps<{ pid: number; info: any }>()
defineEmits(['go'])
const errText = (e: any) => (typeof e === 'string' ? e : e?.msg || '操作失败')

// ---- 计费单价:每张助手卡片都要显示自己的收费项;0 = 演示模式或后台设为免费 ----
const prices = reactive<Record<string, number>>({})
// 模型计费口径:确认弹窗的开关看 enabled(是否计费),不能再看 score>0(恒为 0)
const chargeEnabled = ref(false)
const loadPrices = async () => {
    try {
        const cfg: any = (await geoChargeConfig()) || {}
        chargeEnabled.value = !!cfg.enabled
        const charges: any[] = Array.isArray(cfg) ? cfg : cfg.list || []
        for (const c of charges) prices[c.scene] = Number(c.score || 0)
    } catch (e) { /* 取不到配置按演示模式,不弹计费确认 */ }
}

// ---- GEO 报告:落库快照。查看免费;生成/重新生成计费 geo_report ----
const showReport = ref(false)
const reportLoading = ref(false)
const latest = ref<any>(null)
const report = computed(() => latest.value?.report || null)
// 分平台策略的档位配色：标杆=绿(可复制)、潜力=蓝(优先突破)、薄弱=橙(需补齐)
const TIER_TYPE: Record<string, string> = { 标杆: 'success', 潜力: 'primary', 薄弱: 'warning' }
// rank=0 表示可见度为 0、未参与排名(后端「未上榜」语义),不能显示成"第0名"
const fmtRank = (r: any) => (Number(r) > 0 ? `第${r}名` : '未上榜')
const fmtRankNum = (r: any) => (Number(r) > 0 ? String(r) : '未上榜')
const reportPrice = computed(() => prices.geo_report || 0)
const reportPriceText = computed(() => {
    // 模型计费口径:不再有固定场景价,查看已生成报告仍免费
    return latest.value?.exists ? '查看免费,重新生成按模型用量计费' : '按模型用量计费,失败不扣'
})

const contentLoading = ref(false)
const loadLatest = async () => {
    try { latest.value = await geoReportLatest(props.pid) } catch (e) { /* 无报告按未生成处理 */ }
}
onMounted(async () => {
    contentLoading.value = true
    try {
        await Promise.all([loadPrices(), loadLatest()])
    } finally {
        contentLoading.value = false
    }
})

const generateReport = async () => {
    if (reportLoading.value) return // 报告卡整卡可点,无禁用态,连点会生成多份报告多次扣费
    if (chargeEnabled.value) {
        try {
            await geoConfirm({
                title: '生成 GEO 报告',
                message: '生成后可免费反复查看，直到下次重新生成。失败不扣算力。',
                confirmText: '生成报告',
                tone: 'info',
                facts: [{ label: '计费方式', value: '按模型用量计费', emphasize: true }],
                note: '含报告内 AI 优化建议刷新,失败不扣'
            })
        } catch { return false }
    }
    reportLoading.value = true
    try {
        latest.value = await geoReportGenerate(props.pid)
        showReport.value = true
        return true
    } catch (e) { ElMessage.error(errText(e)); return false } finally { reportLoading.value = false }
}

// 卡片按钮:有报告=查看(免费),无报告=生成(计费)
const openReport = async () => {
    if (latest.value?.exists) { showReport.value = true; return }
    await generateReport()
}
// 查看报告弹窗内的「重新生成」
const regenReport = () => generateReport()
const printReport = () => window.print()

// 导出 PDF：把报告 DOM 光栅化后按 A4 分页。避开表格/卡片被切断。
const reportRef = ref<HTMLElement | null>(null)
const pdfBusy = ref(false)
const exportPdf = async () => {
    const el = reportRef.value
    if (!el || pdfBusy.value) return
    pdfBusy.value = true
    const msg = ElMessage({ message: '正在生成 PDF，报告较长请稍候…', type: 'info', duration: 0 })
    try {
        const { exportElementToPdf } = await import('@/utils/exportPdf')
        const name = `${report.value?.brand || 'GEO'}_GEO诊断报告_${report.value?.date || ''}`.replace(/[\\/:*?"<>|]/g, '')
        await exportElementToPdf(el, { filename: name, avoidBreakSelector: 'table,.rep-card,.rep-matrix,.no-break' })
        ElMessage.success('PDF 已生成')
    } catch (e) {
        ElMessage.error('PDF 生成失败：' + errText(e))
    } finally {
        msg.close()
        pdfBusy.value = false
    }
}

// ---- 文章转口播稿 → 数字人纯口播视频(对齐 product) ----
// GEO 侧不再生成视频,只负责把文章转成口播稿,然后带着文案跳到数字人创建页。
// 文案生成走的就是数字人创建页「AI 生成」按钮背后的那个接口(/kb.robot/getCopywriting),
// sn=5「口播文案」;persona_id 必须传 0 —— 后端 KbRobotLogic 在 persona_id>0 时
// 会用人设描述整个覆盖 keywords,正文会被静默丢弃。
const SN_ORAL_SCRIPT = 5
const showScript = ref(false)
const scriptContents = ref<any[]>([])
const scriptContentId = ref<number | null>(null)
const scriptLength = ref(300)
const scriptText = ref('')
const scriptLoading = ref(false)

const openScript = async () => {
    showScript.value = true
    scriptText.value = ''
    try {
        scriptContents.value = (await geoContents({ project_id: props.pid })) || []
    } catch (e) { ElMessage.error(errText(e)) }
}

const genScript = async () => {
    const article = scriptContents.value.find((c) => c.id === scriptContentId.value)
    if (!article) return
    // 去掉 markdown 记号再喂给模型,并控制长度(后端对 keywords 没有任何截断保护)
    const plain = `${article.title}\n${String(article.body || '').replace(/[#*>`[\]()]+/g, ' ')}`.slice(0, 3000)
    scriptLoading.value = true
    try {
        const res: any = await getCopyWritingGenerate({
            sn: SN_ORAL_SCRIPT,
            keywords: plain,
            number: 1,
            length: scriptLength.value,
            type: 1,
            persona_id: 0,
        })
        const text = res?.content?.[0] || ''
        if (!text) return ElMessage.warning('没有生成出内容,请重试')
        scriptText.value = text
    } catch (e) { ElMessage.error(errText(e)) } finally { scriptLoading.value = false }
}

const { put } = useCopywritingHandoff()
const toDigitalHuman = () => {
    const key = put({ content: scriptText.value.trim(), from: 'GEO 文章' })
    if (!key) return ElMessage.error('浏览器不支持暂存文案,请手动复制过去')
    showScript.value = false
    navigateTo(`/app/digital_human?type=${DH_PURE_ORAL_VIDEO}&prefill=${key}`)
}
</script>

<style lang="scss">
/* 打印时只输出报告主体(ElDialog 传送到 body,需全局样式) */
@media print {
    body * { visibility: hidden !important; }
    .geo-report-print, .geo-report-print * { visibility: visible !important; }
    .geo-report-print { position: absolute !important; left: 0; top: 0; width: 100%; max-height: none !important; overflow: visible !important; }
}
</style>

<style lang="scss" scoped>
.agent-card {
    @apply bg-white rounded-[20px] border border-br p-5 cursor-pointer flex flex-col min-h-[220px] transition-all duration-200;
    &:hover {
        @apply border-primary;
        box-shadow: 0 12px 24px -8px rgba(0, 101, 251, 0.08);
        transform: translateY(-2px);
    }
}
.rep-t { @apply font-semibold text-slate-900 mb-2; }
.rep-card { @apply rounded-xl bg-slate-50 p-3 text-center; }
.rep-num { @apply text-xl font-semibold text-slate-900; }
.rep-lab { @apply text-xs text-slate-400 mt-0.5; }
.rep-matrix { @apply w-full text-xs text-slate-600; border-collapse: collapse; }
.rep-matrix th { @apply bg-[#f8fafc] text-slate-500 font-bold whitespace-nowrap; }
.rep-matrix th, .rep-matrix td { @apply border border-[#eef2f7] px-2 py-1.5 text-center whitespace-nowrap; }
.rep-matrix tr.is-self td { @apply text-primary font-bold; background: rgba(0, 101, 251, 0.05); }
/* 报告内的标签一律用 inline-block span 而非 ElTag:导出 PDF 走 html2canvas 光栅化,
   inline-flex 布局的 ElTag 文字会被画到框外,PDF 里只剩空框 */
.rep-tag { @apply inline-block align-middle whitespace-nowrap rounded-md border px-1.5 text-xs leading-5; }
.rep-tag--plain { color: #0065fb; border-color: #b3d1fe; background: #fff; }
.rep-tag--primary { color: #0065fb; border-color: #cce0fe; background: #ecf5ff; }
.rep-tag--success { color: #529b2e; border-color: #d1edc4; background: #f0f9eb; }
.rep-tag--warning { color: #b88230; border-color: #f3d19e; background: #fdf6ec; }
.rep-tag--info { color: #73767a; border-color: #dedfe0; background: #f4f4f5; }
</style>
