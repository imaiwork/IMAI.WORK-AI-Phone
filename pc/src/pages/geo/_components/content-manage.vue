<template>
    <div class="space-y-4">
        <!-- tab -->
        <div
            v-if="section === 'publish'"
            class="bg-white rounded-xl border border-br px-5 flex items-center justify-between">
            <GeoSubTabs :model-value="tab" :tabs="publishTabs" class="!border-0" @update:model-value="onPublishTab" />
            <div v-if="tab === 'records' && statsEnabled && statsSummary.count > 0" class="text-slate-500 text-sm">
                已回收 <b class="text-slate-700">{{ statsSummary.count }}</b> 篇 · 播放
                <b class="text-slate-700">{{ fmtNum(statsSummary.views) }}</b> · 互动
                <b class="text-slate-700">{{ fmtNum(statsSummary.interactions) }}</b>
            </div>
        </div>

        <!-- ===== 文章列表 ===== -->
        <template v-if="tab === 'list'">
            <div
                class="bg-white rounded-xl border border-br overflow-hidden min-h-[360px] flex flex-col"
                v-spin="{ show: contentLoading, text: '加载中...' }">
                <div class="shrink-0 px-5 py-3 flex items-center gap-3 flex-wrap border-b border-[#F1F5F9]">
                    <ElSelect v-model="listFilter.topic_id" placeholder="全部话题" clearable style="width:170px" @change="reloadContents">
                        <ElOption v-for="t in topics" :key="t.id" :label="t.name" :value="t.id" />
                    </ElSelect>
                    <ElInput v-model="listFilter.keyword" placeholder="搜索标题" style="width:220px" clearable @keyup.enter="reloadContents" @clear="reloadContents">
                        <template #suffix>
                            <Icon name="el-icon-Search" :size="14" class="cursor-pointer text-slate-400" @click="reloadContents" />
                        </template>
                    </ElInput>
                    <ElCheckbox v-model="listFilter.unpublished" :true-value="1" :false-value="0" class="ml-auto" @change="reloadContents">仅看尚未发布的文章</ElCheckbox>
                    <ElButton type="primary" class="!h-11 !rounded-xl" @click="$emit('go', 'generate')">生成文章</ElButton>
                </div>
                <div v-if="contents.length" class="divide-y divide-[#F1F5F9]">
                    <div
                        v-for="row in contents"
                        :key="row.id"
                        class="flex items-center gap-4 px-5 py-4 hover:bg-slate-50/80">
                        <div class="min-w-0 flex-1">
                            <button
                                type="button"
                                class="block max-w-full text-left text-sm font-medium text-slate-900 truncate hover:text-primary"
                                @click="openView(row)">
                                {{ row.title || "未命名文章" }}
                            </button>
                            <div class="mt-1 flex items-center gap-2 text-xs text-slate-400 min-w-0">
                                <span class="truncate">{{ row.topic_name || row.content_type || "未分话题" }}</span>
                                <span class="text-slate-200">·</span>
                                <span :class="row.status === 1 ? 'text-emerald-600' : ''">{{
                                    row.status === 1 ? "已发布" : "草稿"
                                }}</span>
                                <span class="text-slate-200">·</span>
                                <span class="tabular-nums shrink-0">{{ fmtGeoTime(row.create_time) }}</span>
                            </div>
                        </div>
                        <div class="shrink-0 flex items-center">
                            <button
                                type="button"
                                class="h-8 px-2.5 text-sm text-slate-500 hover:text-primary"
                                @click="openEdit(row)">
                                编辑
                            </button>
                            <button
                                type="button"
                                class="h-8 px-2.5 text-sm text-slate-500 hover:text-primary"
                                @click="openPublish(row)">
                                发布
                            </button>
                            <button
                                type="button"
                                class="h-8 px-2.5 text-sm text-slate-500 hover:text-primary"
                                @click="openView(row)">
                                预览
                            </button>
                            <ElDropdown trigger="click" @command="(cmd) => onArticleMore(cmd, row)">
                                <button
                                    type="button"
                                    class="h-8 w-8 grid place-items-center text-slate-400 hover:text-slate-700"
                                    aria-label="更多操作">
                                    <Icon name="el-icon-MoreFilled" :size="16" />
                                </button>
                                <template #dropdown>
                                    <ElDropdownMenu>
                                        <ElDropdownItem command="download">下载</ElDropdownItem>
                                        <ElDropdownItem command="register">登记发布</ElDropdownItem>
                                        <ElDropdownItem command="delete" divided>
                                            <span class="text-rose-500">删除</span>
                                        </ElDropdownItem>
                                    </ElDropdownMenu>
                                </template>
                            </ElDropdown>
                        </div>
                    </div>
                </div>
                <div v-else-if="!contentLoading" class="flex-1 grid place-items-center py-16">
                    <GeoEmpty description="暂无文章">
                        <template #action>
                            <ElButton type="primary" @click="$emit('go', 'generate')">生成第一篇文章</ElButton>
                        </template>
                    </GeoEmpty>
                </div>
                <div v-if="cPage.total > cPage.limit" class="shrink-0 flex justify-end px-5 py-3 border-t border-[#F1F5F9]">
                    <ElPagination
                        v-model:current-page="cPage.page"
                        v-model:page-size="cPage.limit"
                        :total="cPage.total"
                        :page-sizes="[20, 50, 100]"
                        layout="total, sizes, prev, pager, next"
                        background
                        @current-change="loadContents"
                        @size-change="() => { cPage.page = 1; loadContents() }" />
                </div>
            </div>
        </template>

        <!-- ===== 发布记录 ===== -->
        <template v-if="tab === 'records'">
            <div
                class="bg-white rounded-xl border border-br overflow-hidden min-h-[280px] flex flex-col"
                v-spin="{ show: contentLoading, text: '加载中...' }">
                <div v-if="records.length" class="divide-y divide-[#F1F5F9]">
                    <div
                        v-for="row in records"
                        :key="row.id"
                        class="flex items-center gap-4 px-5 py-4 hover:bg-slate-50/80">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="text-xs text-slate-400 tabular-nums shrink-0">#{{ row.id }}</span>
                                <span class="text-sm font-medium text-slate-800 truncate">{{ row.title || "未命名" }}</span>
                            </div>
                            <div class="mt-1 flex items-center gap-2 text-xs text-slate-500 min-w-0">
                                <span class="shrink-0">{{ PUB_MODE[row.mode] || "媒体投稿" }}</span>
                                <span class="text-slate-200">·</span>
                                <span class="shrink-0">{{ row.media_type === "video" ? "视频" : "图文" }}</span>
                                <span class="text-slate-200">·</span>
                                <span class="truncate">{{ row.media_name || row.account || "—" }}</span>
                                <template v-if="row.published_url">
                                    <span class="text-slate-200">·</span>
                                    <a
                                        :href="row.published_url"
                                        target="_blank"
                                        class="text-primary truncate max-w-[220px]"
                                        >{{ row.published_url }}</a>
                                </template>
                                <template v-else-if="row.site_name">
                                    <span class="text-slate-200">·</span>
                                    <span class="truncate">{{ row.site_name }}</span>
                                </template>
                            </div>
                            <div v-if="statsEnabled" class="mt-1 text-xs text-slate-400">
                                <template v-if="row.stat_status === 'ok'">
                                    <span v-if="row.stat_views">播 {{ fmtNum(row.stat_views) }}</span>
                                    <span class="ml-2">赞 {{ fmtNum(row.stat_likes) }}</span>
                                    <span class="ml-2">评 {{ fmtNum(row.stat_comments) }}</span>
                                    <span v-if="row.stat_collects" class="ml-2">藏 {{ fmtNum(row.stat_collects) }}</span>
                                </template>
                                <ElTooltip v-else-if="row.stat_status === 'failed'" :content="row.stat_error || '回收失败'">
                                    <span class="text-amber-600 cursor-help">回收失败</span>
                                </ElTooltip>
                                <span v-else-if="row.stat_status === 'unsupported'">该渠道无数据</span>
                                <span v-else>待回收</span>
                            </div>
                        </div>
                        <div class="shrink-0 flex items-center gap-3">
                            <ElTooltip v-if="row.status === 'failed' && row.error_msg" :content="row.error_msg">
                                <span class="media-chip media-chip--danger">失败</span>
                            </ElTooltip>
                            <span v-else class="media-chip" :class="recStatusChip(row)">{{ PUB_STATUS[row.status] || row.status }}</span>
                            <div class="rec-btns">
                                <button
                                    v-if="row.status === 'pending' && row.mode !== 'phone'"
                                    type="button"
                                    class="rec-btn"
                                    @click="openConfirm(row)">
                                    回填链接
                                </button>
                                <button type="button" class="rec-btn rec-btn--danger" @click="delRecord(row.id)">删除</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else-if="!contentLoading" class="flex-1 grid place-items-center py-16">
                    <GeoEmpty description="暂无发布记录，可先在文章列表中发布" />
                </div>
            </div>
        </template>

        <!-- ===== 媒体库投稿 ===== -->
        <template v-if="tab === 'media'">
            <div
                class="bg-white rounded-xl border border-br overflow-hidden min-h-[360px]"
                v-spin="{ show: contentLoading, text: '加载中...' }">
                <div class="px-5 py-3.5 border-b border-[#F1F5F9]">
                    <div class="flex items-center gap-3 flex-wrap">
                        <ElSelect
                            v-model="mediaFilter.type"
                            placeholder="类型"
                            clearable
                            style="width: 130px"
                            @change="loadMedia">
                            <ElOption v-for="t in mediaTypes" :key="t.value" :label="t.label" :value="t.value" />
                        </ElSelect>
                        <ElSelect
                            v-model="mediaFilter.content_form"
                            placeholder="投稿类型"
                            clearable
                            style="width: 120px"
                            @change="loadMedia">
                            <ElOption v-for="f in mediaContentForms" :key="f.value" :label="f.label" :value="f.value" />
                        </ElSelect>
                        <ElSelect
                            v-model="mediaFilter.category"
                            placeholder="行业"
                            clearable
                            style="width: 120px"
                            @change="loadMedia">
                            <ElOption v-for="c in mediaCats" :key="c" :label="c" :value="c" />
                        </ElSelect>
                        <ElCheckbox
                            v-model="mediaFilter.can_geo_rank"
                            :true-value="1"
                            :false-value="''"
                            @change="loadMedia"
                            >仅可发 GEO 排名</ElCheckbox
                        >
                        <ElInput
                            v-model="mediaFilter.keyword"
                            placeholder="搜索媒体名"
                            style="width: 168px"
                            clearable
                            @keyup.enter="loadMedia"
                            @clear="loadMedia" />
                        <ElButton type="primary" class="!h-9 !rounded-lg" @click="loadMedia">搜索</ElButton>
                    </div>
                </div>
                <ElTable :data="media" class="geo-plain-table media-table" row-key="id">
                    <ElTableColumn label="媒体" min-width="240">
                        <template #default="{ row }">
                            <div class="flex items-center gap-1.5 min-w-0">
                                <ElTooltip
                                    :content="row.remark || row.category || ''"
                                    :disabled="!(row.remark || row.category)"
                                    placement="top">
                                    <span class="text-sm font-semibold text-slate-800 truncate">{{ row.name }}</span>
                                </ElTooltip>
                                <span
                                    v-if="mediaStatusText(row)"
                                    class="media-chip shrink-0"
                                    :class="mediaStatusChip(row)"
                                    >{{ mediaStatusText(row) }}</span
                                >
                                <span v-if="row.can_geo_rank" class="media-chip media-chip--warn shrink-0"
                                    >GEO可发</span
                                >
                            </div>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="类型" width="120">
                        <template #default="{ row }">
                            <div class="flex flex-wrap gap-1">
                                <span v-for="f in mediaForms(row)" :key="f" class="media-chip">{{
                                    f === "video" ? "视频" : "图文"
                                }}</span>
                            </div>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="PC / 移动" width="100">
                        <template #default="{ row }">
                            <span class="text-sm text-slate-600 tabular-nums"
                                >{{ row.pc_weight }} / {{ row.mobile_weight }}</span
                            >
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="出稿率" width="80">
                        <template #default="{ row }">
                            <span class="text-sm text-emerald-700 tabular-nums">{{ row.success_rate }}%</span>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="发稿速度" width="88">
                        <template #default="{ row }">
                            <span class="text-sm text-slate-600">{{ row.publish_speed || "—" }}</span>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="外链" width="80">
                        <template #default="{ row }">
                            <span class="media-chip" :class="row.allow_url ? 'media-chip--ok' : 'media-chip--mute'">{{
                                row.allow_url ? "可带" : "不可带"
                            }}</span>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="价格" width="80">
                        <template #default="{ row }">
                            <span v-if="isMediaFree(row)" class="text-sm text-emerald-700">免费</span>
                            <span v-else class="text-sm text-rose-600 tabular-nums">¥{{ row.price }}</span>
                        </template>
                    </ElTableColumn>
                    <ElTableColumn label="操作" width="108" fixed="right" align="right">
                        <template #default="{ row }">
                            <ElButton
                                v-if="needAuth(row)"
                                size="small"
                                class="!h-8 !rounded-lg"
                                @click="$emit('go', 'set_account')">
                                去授权
                            </ElButton>
                            <ElButton
                                v-else
                                type="primary"
                                size="small"
                                class="!h-8 !rounded-lg"
                                :disabled="row.status !== 1"
                                @click="row.type === 'ai_phone' ? openAiPublish(row) : openMediaPublish([row])">
                                投稿
                            </ElButton>
                        </template>
                    </ElTableColumn>
                    <template #empty><GeoEmpty description="没有符合条件的媒体" /></template>
                </ElTable>
            </div>
        </template>

        <GeoDialog
            v-model="showEdit"
            layout="panel"
            :title="editRow?.id ? '编辑文章' : '文章'"
            width="760px"
            confirm-text="保存"
            :confirm-loading="saving"
            @confirm="saveEdit">
            <ElForm label-position="top">
                <ElFormItem label="标题"><ElInput v-model="editRow.title" /></ElFormItem>
                <ElFormItem label="正文(Markdown)">
                    <div class="flex items-center gap-2 mb-2">
                        <input ref="editImgInput" type="file" accept="image/png,image/jpeg,image/webp" class="hidden" @change="insertEditImage" />
                        <ElButton size="small" class="!h-8 !rounded-lg" :loading="editImgBusy" @click="editImgInput?.click()">插入图片</ElButton>
                        <span class="text-xs text-slate-500">上传后插入正文，公众号投稿可用作封面</span>
                    </div>
                    <ElInput v-model="editRow.body" type="textarea" :autosize="{ minRows: 14, maxRows: 22 }" />
                </ElFormItem>
            </ElForm>
        </GeoDialog>
        <GeoDialog v-model="showView" layout="panel" :title="viewRow?.title || '文章预览'" width="720px">
            <div class="whitespace-pre-wrap text-slate-700 text-sm max-h-[60vh] overflow-y-auto leading-relaxed">
                {{ viewRow?.body }}
            </div>
            <template #footer>
                <div class="flex gap-3">
                    <ElButton class="geo-dialog__btn !flex-1" @click="copyBody">复制内容</ElButton>
                    <ElButton type="primary" class="geo-dialog__btn !flex-1 !font-semibold" @click="showView = false"
                        >关闭</ElButton
                    >
                </div>
            </template>
        </GeoDialog>

        <GeoDialog
            v-model="showRegister"
            layout="panel"
            title="自有发布登记"
            :desc="`已在外部渠道发布过《${regRow?.title || ''}》？登记链接后计入发布台账与引用溯源。`"
            width="620px"
            confirm-text="提交"
            :confirm-loading="registering"
            @confirm="submitRegister">
            <ElForm label-position="top">
                <ElFormItem label="类型" required>
                    <ElRadioGroup v-model="regForm.channel_type">
                        <ElRadio value="portal">综合门户</ElRadio>
                        <ElRadio value="we_media">自媒体</ElRadio>
                        <ElRadio value="official">品牌官网</ElRadio>
                        <ElRadio value="baike">百科</ElRadio>
                    </ElRadioGroup>
                </ElFormItem>
                <ElFormItem label="站点" required>
                    <ElSelect
                        v-model="regForm.site_name"
                        filterable
                        allow-create
                        default-first-option
                        class="w-full"
                        placeholder="选择或输入发布站点">
                        <ElOption v-for="s in REG_SITES[regForm.channel_type]" :key="s" :label="s" :value="s" />
                    </ElSelect>
                </ElFormItem>
                <ElFormItem label="媒体号" required
                    ><ElInput v-model="regForm.account" placeholder="请输入媒体号名称"
                /></ElFormItem>
                <ElFormItem label="文章链接" required
                    ><ElInput v-model="regForm.url" type="textarea" :rows="2" placeholder="请输入文章链接地址"
                /></ElFormItem>
                <ElFormItem label="发布时间" required
                    ><ElDatePicker
                        v-model="regForm.publish_time"
                        type="datetime"
                        value-format="YYYY-MM-DD HH:mm:ss"
                        placeholder="请选择发布时间"
                        class="w-full"
                /></ElFormItem>
            </ElForm>
        </GeoDialog>

        <GeoDialog
            v-model="showMediaPublish"
            layout="panel"
            title="媒体投稿"
            :desc="`投递到 ${pubMedia.map((m: any) => m.name).join('、')}`"
            width="620px"
            confirm-text="创建投递任务"
            :confirm-loading="pubBusy"
            :confirm-disabled="pubType === 'article' ? !pubContentId : !pubVideoId"
            @confirm="doMediaPublish">
            <div v-if="pubAuthCount" class="text-emerald-500 text-xs mb-1">
                其中 {{ pubAuthCount }} 家已授权,将用你自己的账号经官方 API 直发。
            </div>
            <div class="text-amber-500 text-xs mb-3">
                仅支持你已授权的平台直发,未授权平台请先到「设置-授权账号」完成授权。
            </div>
            <div class="flex items-center gap-3 mb-3">
                <span class="text-slate-500 text-sm">投稿类型</span>
                <ElRadioGroup v-model="pubType">
                    <ElRadioButton value="article" :disabled="!pubFormOk('article')">图文</ElRadioButton>
                    <ElRadioButton value="video" :disabled="!pubFormOk('video')">视频</ElRadioButton>
                </ElRadioGroup>
            </div>
            <div v-if="pubType === 'article'" class="space-y-2 max-h-[320px] overflow-y-auto">
                <button
                    v-for="c in contents"
                    :key="c.id"
                    type="button"
                    class="w-full flex items-center gap-3 rounded-xl border px-3 py-3 text-left"
                    :class="pubContentId === c.id ? 'border-primary bg-primary/5' : 'border-br hover:border-primary'"
                    @click="pubContentId = c.id">
                    <span
                        class="w-4 h-4 rounded-full border-2 shrink-0 grid place-items-center"
                        :class="pubContentId === c.id ? 'border-primary' : 'border-slate-300'">
                        <span v-if="pubContentId === c.id" class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                    </span>
                    <span class="min-w-0 flex-1 text-sm font-medium text-slate-800 truncate">{{ c.title }}</span>
                    <span
                        class="shrink-0 max-w-[7rem] truncate text-xs text-primary bg-primary/5 px-2 py-0.5 rounded-md"
                        >{{ c.topic_name || c.content_type }}</span
                    >
                </button>
                <div v-if="!contents.length" class="text-slate-400 text-sm text-center py-6">
                    还没有内容,先去「内容生成」创建
                </div>
            </div>
            <div v-else class="space-y-2 max-h-[320px] overflow-y-auto">
                <button
                    v-for="v in videosDone"
                    :key="v.id"
                    type="button"
                    class="w-full flex items-center gap-3 rounded-xl border px-3 py-3 text-left"
                    :class="pubVideoId === v.id ? 'border-primary bg-primary/5' : 'border-br hover:border-primary'"
                    @click="pubVideoId = v.id">
                    <span
                        class="w-4 h-4 rounded-full border-2 shrink-0 grid place-items-center"
                        :class="pubVideoId === v.id ? 'border-primary' : 'border-slate-300'">
                        <span v-if="pubVideoId === v.id" class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                    </span>
                    <span class="min-w-0 flex-1 text-sm font-medium text-slate-800 truncate">{{ v.title }}</span>
                    <span class="shrink-0 text-xs text-primary bg-primary/5 px-2 py-0.5 rounded-md">{{ v.ratio }}</span>
                </button>
                <div v-if="!videosDone.length" class="text-slate-400 text-sm text-center py-6">
                    还没有生成完成的短视频,先在「GEO 助手-创作短视频」生成
                </div>
            </div>
        </GeoDialog>

        <GeoDialog
            v-model="showAiPublish"
            layout="panel"
            :title="`${aiMedia?.name || ''} · AI 手机发布`"
            :desc="`使用已绑定的 AI 手机账号自动发布到${
                aiMedia?.name || '该平台'
            }。提交后可在「矩阵-我的发布」查看进度。`"
            width="640px"
            confirm-text="立即发布"
            :confirm-loading="aiBusy"
            @confirm="doAiPublish">
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <span class="text-slate-500 text-sm w-16 shrink-0">类型</span>
                    <ElRadioGroup v-model="aiForm.type">
                        <ElRadioButton value="article" :disabled="!mediaForms(aiMedia).includes('article')"
                            >图文</ElRadioButton
                        >
                        <ElRadioButton value="video" :disabled="!mediaForms(aiMedia).includes('video')"
                            >视频</ElRadioButton
                        >
                    </ElRadioGroup>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-slate-500 text-sm w-16 shrink-0">账号</span>
                    <ElSelect
                        v-model="aiForm.account"
                        placeholder="选择该平台已绑定的 AI 手机账号"
                        class="flex-1"
                        filterable>
                        <ElOption
                            v-for="a in aiAccounts"
                            :key="a.id || a.account"
                            :label="aiAccountLabel(a)"
                            :value="a.account">
                            <div class="flex items-center justify-between gap-3 w-full min-w-0">
                                <span class="truncate">{{ a.nickname || a.account_name || a.account }}</span>
                                <span v-if="a.nickname || a.account_name" class="text-xs text-slate-400 shrink-0">{{
                                    a.account
                                }}</span>
                            </div>
                        </ElOption>
                    </ElSelect>
                </div>
                <div v-if="!aiAccounts.length" class="text-amber-500 text-xs -mt-2 ml-[76px]">
                    该平台还没有绑定账号,请先在「AI终端」为设备绑定{{ aiMedia?.name }}账号
                </div>
                <!-- 图文:选文章 + 配图 -->
                <template v-if="aiForm.type === 'article'">
                    <div class="flex items-center gap-3">
                        <span class="text-slate-500 text-sm w-16 shrink-0">文章</span>
                        <ElSelect v-model="aiForm.contentId" placeholder="选择一篇 GEO 文章" class="flex-1" filterable>
                            <ElOption v-for="c in contents" :key="c.id" :label="c.title" :value="c.id" />
                        </ElSelect>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="text-slate-500 text-sm w-16 shrink-0 mt-2">配图</span>
                        <div class="flex-1">
                            <div class="flex flex-wrap gap-2">
                                <div
                                    v-for="(img, i) in aiForm.images"
                                    :key="img"
                                    class="relative w-16 h-16 rounded-lg overflow-hidden border border-br">
                                    <img :src="img" class="w-full h-full object-cover" referrerpolicy="no-referrer" />
                                    <span
                                        class="absolute top-0 right-0 bg-black/50 text-white text-xs px-1 cursor-pointer"
                                        @click="aiForm.images.splice(i, 1)"
                                        >×</span
                                    >
                                </div>
                                <button
                                    v-if="aiForm.images.length < 9"
                                    type="button"
                                    class="w-16 h-16 rounded-lg border border-dashed border-br grid place-items-center text-slate-300 cursor-pointer hover:border-primary hover:text-primary"
                                    :class="showAiImgMenu ? 'border-primary text-primary' : ''"
                                    @click="showAiImgMenu = !showAiImgMenu">
                                    +
                                </button>
                            </div>
                            <div
                                v-if="showAiImgMenu && aiForm.images.length < 9"
                                class="mt-2 w-[300px] rounded-xl border border-br bg-white p-1">
                                <MaterialMenuContent
                                    type="image"
                                    :image-limit="9 - aiForm.images.length"
                                    @action="onAiImageMaterialAction" />
                            </div>
                            <div class="text-slate-400 text-xs mt-1">
                                <span v-if="coverLoading" class="text-primary">AI 封面图生成中,请稍候…</span>
                                <span v-else
                                    >图文发布需 1~9 张配图(平台要求),已选 {{ aiForm.images.length }} 张。文章标题与正文将作为文案</span
                                >
                            </div>
                        </div>
                    </div>
                </template>
                <!-- 视频:素材来源对齐数字人侧(本地上传 / 素材库 / 创作库) -->
                <template v-else>
                    <div class="flex items-start gap-3">
                        <span class="text-slate-500 text-sm w-16 shrink-0 pt-2">视频</span>
                        <div class="flex-1 min-w-0">
                            <div v-if="aiVideo" class="flex items-center gap-3 p-2.5 rounded-xl border border-br">
                                <img
                                    v-if="aiVideo.cover"
                                    :src="aiVideo.cover"
                                    class="w-16 h-16 rounded-lg object-cover bg-slate-100 shrink-0"
                                    alt="" />
                                <video
                                    v-else
                                    :src="aiVideo.url"
                                    class="w-16 h-16 rounded-lg object-cover bg-black shrink-0"
                                    preload="metadata"></video>
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm text-slate-700 truncate">{{ aiVideo.title || '视频素材' }}</div>
                                    <div class="text-xs text-slate-400 truncate">{{ aiVideo.url }}</div>
                                </div>
                                <ElButton link type="danger" @click="aiVideo = null">移除</ElButton>
                            </div>
                            <template v-else>
                                <ElButton class="!rounded-lg" @click="showAiVideoMenu = !showAiVideoMenu"
                                    >＋ 添加视频素材</ElButton
                                >
                                <div
                                    v-if="showAiVideoMenu"
                                    class="mt-2 w-[300px] rounded-xl border border-br bg-white p-1">
                                    <MaterialMenuContent type="video" :video-limit="1" @action="onAiMaterialAction" />
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="text-slate-500 text-sm w-16 shrink-0">标题</span>
                        <ElInput
                            v-model="aiForm.videoTitle"
                            :maxlength="aiTitleLimit || undefined"
                            :show-word-limit="aiTitleLimit > 0"
                            class="flex-1"
                            :placeholder="aiTitleLimit > 0 ? `视频标题(该平台上限 ${aiTitleLimit} 字)` : '视频标题'" />
                        <ElSelect
                            v-model="aiVideoFromContent"
                            placeholder="从文章带入"
                            clearable
                            filterable
                            style="width:180px"
                            @change="fillVideoCopyFromContent">
                            <ElOption v-for="c in contents" :key="c.id" :label="c.title" :value="c.id" />
                        </ElSelect>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="text-slate-500 text-sm w-16 shrink-0 pt-2">文案</span>
                        <ElInput
                            v-model="aiForm.videoDesc"
                            type="textarea"
                            :rows="3"
                            class="flex-1"
                            placeholder="发布文案(可选,不填则使用标题)" />
                    </div>
                </template>
            </div>
        </GeoDialog>

        <!-- 视频素材选择弹窗(复用数字人侧组件) -->
        <ChooseMaterial
            v-if="showAiChooseMaterial"
            ref="aiChooseMaterialRef"
            type="video"
            :limit="1"
            @select="onAiPickMaterial"
            @close="showAiChooseMaterial = false" />
        <ChooseHistory
            v-if="showAiChooseHistory"
            ref="aiChooseHistoryRef"
            type="video"
            :multiple="false"
            :limit="1"
            @select="onAiPickMaterial"
            @close="showAiChooseHistory = false" />

        <!-- 图文配图的素材库/创作库选择(多选,受 9 张上限约束) -->
        <ChooseMaterial
            v-if="showAiImgMaterial"
            ref="aiImgMaterialRef"
            type="image"
            :limit="9 - aiForm.images.length"
            @select="onAiPickImages"
            @close="showAiImgMaterial = false" />
        <ChooseHistory
            v-if="showAiImgHistory"
            ref="aiImgHistoryRef"
            type="image"
            :multiple="true"
            :limit="9 - aiForm.images.length"
            @select="onAiPickImages"
            @close="showAiImgHistory = false" />

        <GeoDialog
            v-model="showConfirm"
            title="回填已发布链接"
            :desc="`在「${confirmRow?.media_name || ''}」发布成功后，把文章链接填在这里。`"
            width="480px"
            confirm-text="确认已发布"
            :confirm-disabled="!confirmUrl.trim()"
            @confirm="doConfirm">
            <ElInput v-model="confirmUrl" placeholder="https://..." />
        </GeoDialog>
    </div>
</template>

<script setup lang="ts">
import { ElMessage } from "element-plus";
import GeoDialog from "./geo-dialog.vue";
import { geoConfirm } from "../_composables/geo-confirm";
import {
    geoContents,
    geoContentUpdate,
    geoContentDelete,
    geoContentExport,
    geoTopics,
    geoPublishList,
    geoPublishConfirm,
    geoPublishDelete,
    geoPublishRegister,
    geoMedia,
    geoMediaFilters,
    geoPublishCreate,
    geoVideoList,
    geoAuthPlatforms,
    geoAuthAccountCheck,
    geoPublishPhoneRegister,
    geoContentCover,
} from "@/api/geo";
import { getPublishAccountList, addMatrixTask, publishDeviceTask, checkTaskPublishTime } from "@/api/device";
import MaterialMenuContent from "@/pages/app/digital_human/_components/material-menu-content.vue";
import ChooseMaterial from "@/pages/app/digital_human/_components/choose-material.vue";
import ChooseHistory from "@/pages/app/digital_human/_components/choose-history.vue";
import { uploadImage } from "@/api/app";
import { useGeoLoading } from "../_composables/use-geo-loading";
import { fmtGeoTime } from "../_composables/geo-time";
import GeoSubTabs from "./geo-sub-tabs.vue";
import GeoEmpty from "./geo-empty.vue";

const props = withDefaults(defineProps<{ pid: number; info: any; section?: "list" | "publish" }>(), {
    section: "list",
});
const emit = defineEmits(["go"]);

const publishTabs = [
    { key: "records", label: "发布记录" },
    { key: "media", label: "媒体库投稿" },
];
const errText = (e: any) => (typeof e === "string" ? e : e?.msg || "操作失败");

const PUB_STATUS: Record<string, string> = {
    pending: "待发布",
    publishing: "发布中",
    published: "已发布",
    failed: "失败",
};
const PUB_MODE: Record<string, string> = {
    register: "自有发布",
    api: "媒体投稿·自动",
    auth: "授权账号直发",
    phone: "AI手机发布",
    manual: "媒体投稿·代发",
};
const recStatusChip = (row: any) => {
    if (row.status === "published") return "media-chip--ok";
    if (row.status === "failed") return "media-chip--danger";
    if (row.status === "publishing") return "media-chip--warn";
    return "media-chip--mute";
};
const REG_SITES: Record<string, string[]> = {
    portal: [
        "56视频",
        "中国工业制造网",
        "博客园",
        "中华网科技",
        "凤凰网",
        "腾讯网",
        "IT之家",
        "新浪网",
        "商广网",
        "雪球网",
        "界面新闻",
        "ZAKER",
        "中国企业报道",
        "新浪新闻",
        "中关村在线",
        "太平洋科技网",
        "凤凰网新闻",
        "中国产业新闻网",
        "经济导报网",
        "环球财经网",
        "新京报",
        "中国经济新闻网",
        "腾讯新闻",
        "企业家日报网",
        "其他",
    ],
    we_media: ["知乎", "百家号", "今日头条", "搜狐号", "微信公众号", "B站专栏", "小红书", "其他"],
    official: ["品牌官网", "官方博客", "其他"],
    baike: ["百度百科", "搜狗百科", "360百科", "其他"],
};

const tab = ref("list");
const topics = ref<any[]>([]);
const { contentLoading, beginLoad, isLatest, endLoad } = useGeoLoading();

// 文章列表
const listFilter = reactive<any>({ topic_id: '', keyword: '', unpublished: 0 })
const contents = ref<any[]>([])
const cPage = reactive({ page: 1, limit: 20, total: 0 })
const loadContents = async () => {
    const seq = beginLoad();
    try {
        const res: any = (await geoContents({ project_id: props.pid, ...listFilter, page: cPage.page, limit: cPage.limit })) || {}
        if (isLatest(seq)) {
            contents.value = Array.isArray(res) ? res : res.list || []
            cPage.total = Array.isArray(res) ? res.length : Number(res.total || 0)
        }
    } catch (e) {
        if (isLatest(seq)) ElMessage.error(errText(e));
    } finally {
        endLoad(seq);
    }
}
// 筛选变化回到第一页
const reloadContents = () => { cPage.page = 1; loadContents() }
const onArticleMore = (cmd: string, row: any) => {
    if (cmd === "download") download(row);
    else if (cmd === "register") openRegister(row);
    else if (cmd === "delete") delContent(row);
};
const delContent = async (row: any) => {
    try {
        await geoConfirm({
            title: "删除文章",
            message: `确定删除《${row.title}》？`,
            confirmText: "删除",
            tone: "danger",
        });
    } catch {
        return;
    }
    try {
        await geoContentDelete(row.id);
        ElMessage.success("已删除");
        loadContents();
    } catch (e) {
        ElMessage.error(errText(e));
    }
};
const download = async (row: any) => {
    try {
        const res: any = await geoContentExport([row.id], "md");
        const bin = atob(res.base64);
        const bytes = new Uint8Array(bin.length);
        for (let i = 0; i < bin.length; i++) bytes[i] = bin.charCodeAt(i);
        const url = URL.createObjectURL(new Blob([bytes], { type: res.mime }));
        const a = document.createElement("a");
        a.href = url;
        a.download = res.filename;
        a.click();
        URL.revokeObjectURL(url);
    } catch (e) {
        ElMessage.error(errText(e));
    }
};

// 编辑/预览
const showEdit = ref(false);
const editRow = ref<any>({});
const saving = ref(false);
const editImgInput = ref<HTMLInputElement>();
const editImgBusy = ref(false);
const insertEditImage = async (ev: Event) => {
    const input = ev.target as HTMLInputElement;
    const file = input.files?.[0];
    input.value = "";
    if (!file) return;
    editImgBusy.value = true;
    try {
        const res: any = await uploadImage({ file });
        if (!res?.uri) return ElMessage.error("上传未返回图片地址");
        const md = `\n\n![](${res.uri})\n`;
        editRow.value.body = String(editRow.value.body || "") + md;
        ElMessage.success("已插入图片");
    } catch (e) {
        ElMessage.error(errText(e));
    } finally {
        editImgBusy.value = false;
    }
};
const openEdit = (row: any) => {
    editRow.value = { ...row };
    showEdit.value = true;
};
const saveEdit = async () => {
    saving.value = true;
    try {
        await geoContentUpdate({ id: editRow.value.id, title: editRow.value.title, body: editRow.value.body });
        ElMessage.success("已保存");
        showEdit.value = false;
        loadContents();
    } catch (e) {
        ElMessage.error(errText(e));
    } finally {
        saving.value = false;
    }
};
const showView = ref(false);
const viewRow = ref<any>(null);
const openView = (row: any) => {
    viewRow.value = row;
    showView.value = true;
};
const copyBody = () => {
    navigator.clipboard?.writeText(viewRow.value?.body || "");
    ElMessage.success("已复制");
};

// 发布记录
const records = ref<any[]>([]);
// 投稿效果:未接 TikHub 时 statsEnabled=false,整列与汇总都不出现,
// 避免给用户看一排永远是 0 的假指标
const statsEnabled = ref(false);
const statsSummary = ref<any>({ count: 0, views: 0, interactions: 0 });
const fmtNum = (n: number) => {
    const v = Number(n) || 0;
    return v >= 10000 ? (v / 10000).toFixed(1).replace(/\.0$/, "") + "w" : String(v);
};
const loadRecords = async () => {
    const seq = beginLoad();
    try {
        const res: any = await geoPublishList(props.pid);
        if (!isLatest(seq)) return;
        records.value = res?.list || [];
        statsEnabled.value = !!res?.stats_enabled;
        statsSummary.value = res?.stats_summary || { count: 0, views: 0, interactions: 0 };
    } catch (e) {
        if (isLatest(seq)) ElMessage.error(errText(e));
    } finally {
        endLoad(seq);
    }
};
const delRecord = async (id: number) => {
    try {
        await geoConfirm({
            title: "删除发布记录",
            message: "确定删除这条发布记录？",
            confirmText: "删除",
            tone: "danger",
        });
    } catch {
        return;
    }
    try {
        await geoPublishDelete(id);
        ElMessage.success("已删除");
        loadRecords();
    } catch (e) {
        ElMessage.error(errText(e));
    }
};
const showConfirm = ref(false);
const confirmRow = ref<any>(null);
const confirmUrl = ref("");
const openConfirm = (row: any) => {
    confirmRow.value = row;
    confirmUrl.value = row.published_url || "";
    showConfirm.value = true;
};
const doConfirm = async () => {
    try {
        await geoPublishConfirm(confirmRow.value.id, confirmUrl.value.trim());
        ElMessage.success("已标记为已发布");
        showConfirm.value = false;
        loadRecords();
    } catch (e) {
        ElMessage.error(errText(e));
    }
};

// 登记
const showRegister = ref(false);
const regRow = ref<any>(null);
const registering = ref(false);
const regForm = reactive<any>({ channel_type: "portal", site_name: "", account: "", url: "", publish_time: "" });
const openRegister = (row: any) => {
    regRow.value = row;
    Object.assign(regForm, { channel_type: "portal", site_name: "", account: "", url: "", publish_time: "" });
    showRegister.value = true;
};
const submitRegister = async () => {
    if (!regForm.site_name || !regForm.account || !regForm.url.trim() || !regForm.publish_time)
        return ElMessage.warning("请填写完整登记信息");
    registering.value = true;
    try {
        await geoPublishRegister({ project_id: props.pid, content_id: regRow.value.id, ...regForm });
        ElMessage.success("已登记");
        showRegister.value = false;
        loadContents();
        if (tab.value === "records") loadRecords();
    } catch (e) {
        ElMessage.error(errText(e));
    } finally {
        registering.value = false;
    }
};

// 媒体库投稿
const media = ref<any[]>([]);
const mediaTypes = ref<any[]>([]);
const mediaContentForms = ref<any[]>([]);
const mediaCats = ref<string[]>([]);
const mediaFilter = reactive<any>({ type: "", content_form: "", category: "", can_geo_rank: "", keyword: "" });
// 已授权平台(平台标识 => 可直发):媒体命中时免代发费
const authMap = ref<Record<string, boolean>>({});
const authAccountIds = ref<Record<string, number>>({});
// 已打通官方发布通道的平台(公众号/博客园/百家号/语雀):未授权时引导去授权
const authPublishMap = ref<Record<string, boolean>>({});
const authEnabled = (row: any) => !!(row?.platform_code && authMap.value[row.platform_code]);
const needAuth = (row: any) =>
    !!(row?.platform_code && authPublishMap.value[row.platform_code] && !authMap.value[row.platform_code]);
const canSelectMedia = (row: any) => row?.status === 1 && !needAuth(row);
const isMediaFree = (row: any) => row?.type === "ai_phone" || authEnabled(row) || Number(row?.price || 0) === 0;
const mediaStatusText = (row: any) => {
    if (row.status !== 1) return "未开通";
    if (row.type === "ai_phone") return "AI手机";
    if (needAuth(row)) return "需授权";
    if (authEnabled(row)) return "已授权";
    return "";
};
const mediaStatusChip = (row: any) => {
    if (row.status !== 1) return "media-chip--mute";
    if (needAuth(row)) return "media-chip--warn";
    if (row.type === "ai_phone") return "media-chip--ai";
    if (authEnabled(row)) return "media-chip--ok";
    return "";
};
const mediaForms = (row: any) =>
    String(row?.content_form || "article")
        .split(",")
        .filter(Boolean);
// 已生成完成的短视频(视频投稿的素材源)
const videos = ref<any[]>([]);
const videosDone = computed(() => videos.value.filter((v) => v.status === "success" && v.video_url));
const loadMedia = async () => {
    const seq = beginLoad();
    try {
        if (!mediaTypes.value.length) {
            const f: any = await geoMediaFilters();
            mediaTypes.value = f.type || [];
            mediaContentForms.value = f.content_form || [];
            mediaCats.value = (f.category || []).filter(Boolean);
        }
        const res = (await geoMedia(mediaFilter)) || [];
        if (isLatest(seq)) media.value = res;
    } catch (e) {
        if (isLatest(seq)) ElMessage.error(errText(e));
    } finally {
        endLoad(seq);
    }
    // 授权状态与短视频列表失败不阻塞媒体库展示
    try {
        const ps: any[] = (await geoAuthPlatforms()) || [];
        const map: Record<string, boolean> = {};
        const publishMap: Record<string, boolean> = {};
        const ids: Record<string, number> = {};
        ps.forEach((p) => {
            if (p.can_publish) publishMap[p.platform] = true;
            if (p.authorized && p.enabled && p.can_publish) map[p.platform] = true;
            if (p.authorized && p.account_id) ids[p.platform] = Number(p.account_id);
        });
        authMap.value = map;
        authPublishMap.value = publishMap;
        authAccountIds.value = ids;
    } catch (e) {
        /* 未授权按代发展示 */
    }
    try {
        videos.value = (await geoVideoList(props.pid)) || [];
    } catch (e) {
        /* 忽略 */
    }
};
const onPublishTab = (key: string) => {
    tab.value = key;
    if (key === "records") loadRecords();
    if (key === "media") loadMedia();
};
watch(
    () => props.section,
    (s) => {
        if (s === "publish" && tab.value === "list") {
            tab.value = "records";
            loadRecords();
        }
        if (s === "list") tab.value = "list";
    },
    { immediate: true },
);
const showMediaPublish = ref(false);
const pubMedia = ref<any[]>([]);
const pubContentId = ref<number | null>(null);
const pubVideoId = ref<number | null>(null);
const pubType = ref<"article" | "video">("article");
const pubBusy = ref(false);
// 已授权媒体不收代发费,合计只算未授权部分
const pubMediaCost = computed(() =>
    pubMedia.value.reduce((a, m) => a + (authEnabled(m) ? 0 : Number(m.price || 0)), 0).toFixed(2),
);
const pubAuthCount = computed(() => pubMedia.value.filter((m) => authEnabled(m)).length);
const pubFormOk = (form: string) => pubMedia.value.some((m) => mediaForms(m).includes(form));
const openMediaPublish = (list: any[]) => {
    pubMedia.value = list.filter((m) => canSelectMedia(m) && m.type !== "ai_phone");
    if (!pubMedia.value.length) return ElMessage.warning("所选媒体未开通");
    pubType.value = pubFormOk("article") ? "article" : "video";
    if (pubType.value === "article" && !contents.value.length)
        return ElMessage.warning("还没有内容,先去「内容生成」创建");
    // 保留文章列表「发布」入口的预选文章,仅在无有效预选时才默认第一篇
    // (无条件覆盖会导致用户预选 A 文章、弹窗却默认投第一篇,涉及付费投递)
    if (!pubContentId.value || !contents.value.some((c) => c.id === pubContentId.value)) {
        pubContentId.value = contents.value[0]?.id ?? null;
    }
    pubVideoId.value = videosDone.value[0]?.id ?? null;
    showMediaPublish.value = true;
};
const doMediaPublish = async () => {
    // 视频投稿以所选视频归属的文章挂台账;图文投稿用所选文章
    let contentId = pubContentId.value;
    let videoId = 0;
    if (pubType.value === "video") {
        const v = videosDone.value.find((x) => x.id === pubVideoId.value);
        if (!v) return;
        videoId = v.id;
        contentId = v.content_id || pubContentId.value;
    }
    if (!contentId) return ElMessage.warning("请选择投递内容");
    // 媒体代发已下线:可投递的只剩授权直发渠道,全程免费,确认框不再出现人民币金额
    try {
        await geoConfirm({
            title: "确认投稿",
            message: "本次全部走你已授权的账号经平台官方接口直发。",
            confirmText: "确认提交",
            cancelText: "再想想",
            tone: "info",
            facts: [{ label: "投递媒体", value: `${pubMedia.value.length} 家(授权直发)` }],
        });
    } catch {
        return;
    }
    pubBusy.value = true;
    // 投稿前预检授权凭据:把"凭据失效/地址配错"拦在创建发布记录之前,
    // 避免落一条失败记录才发现问题。百家号无免费校验接口,跳过(以首次发布结果为准)
    {
        const seen = new Set<number>();
        for (const m of pubMedia.value) {
            const pf = m.platform_code;
            if (!pf || pf === "baijiahao" || !authMap.value[pf]) continue;
            const accId = Number(authAccountIds.value[pf] || 0);
            if (!accId || seen.has(accId)) continue;
            seen.add(accId);
            try {
                await geoAuthAccountCheck(accId);
            } catch (e) {
                ElMessage.error(`「${m.name}」授权检测未通过:${errText(e)}。请到「设置-授权账号」修复后再投稿`);
                pubBusy.value = false;
                return;
            }
        }
    }
    try {
        const res: any = await geoPublishCreate(
            props.pid,
            contentId,
            pubMedia.value.map((m) => m.id),
            pubType.value,
            videoId,
        );
        const tasks = Array.isArray(res?.tasks) ? res.tasks : [];
        const failed = tasks.filter((t: any) => t.status === "failed");
        if (failed.length && failed.length === tasks.length) {
            ElMessage.error(failed[0]?.error_msg || "发布失败");
        } else if (failed.length) {
            ElMessage.warning(`已创建 ${res.count} 个任务，其中 ${failed.length} 个失败`);
        } else {
            ElMessage.success(
                `已创建 ${res.count} 个投递任务${res.auth_count ? `(${res.auth_count} 个已授权直发)` : ""}`,
            );
        }
        showMediaPublish.value = false;
        tab.value = "records";
        loadRecords();
    } catch (e) {
        ElMessage.error(errText(e));
    } finally {
        pubBusy.value = false;
    }
};

// ---- AI 手机发布(小红书/抖音/快手/视频号):走系统矩阵发布(手动发布)通道 ----
// 平台标识 => AI手机账号平台枚举(DeviceEnum:1视频号 3小红书 4抖音 5快手)
const AI_APP_TYPE: Record<string, number> = { sph: 1, xhs: 3, douyin: 4, kuaishou: 5 };
// 平台标题上限(字):超出会被平台拒绝或截断;未列出的平台不限
const AI_TITLE_LIMIT: Record<string, number> = { xhs: 20, douyin: 30 };
const aiTitleLimit = computed(() => AI_TITLE_LIMIT[aiMedia.value?.provider_code] ?? 0);
const clampAiTitle = (t: string) => (aiTitleLimit.value > 0 ? t.slice(0, aiTitleLimit.value) : t);
const showAiPublish = ref(false);
const aiMedia = ref<any>(null);
const aiBusy = ref(false);
const aiAllAccounts = ref<any[]>([]);
const aiForm = reactive<{
    type: "article" | "video";
    account: string;
    contentId: number | null;
    videoId: number | null;
    images: string[];
    videoTitle: string;
    videoDesc: string;
}>({
    type: "article",
    account: "",
    contentId: null,
    videoId: null,
    images: [],
    videoTitle: "",
    videoDesc: "",
});
const aiAccounts = computed(() => {
    const t = AI_APP_TYPE[aiMedia.value?.provider_code] || 0;
    return aiAllAccounts.value.filter((a) => Number(a.type) === t);
});
const aiAccountLabel = (a: any) => {
    const name = String(a?.nickname || a?.account_name || "").trim();
    const id = String(a?.account || "");
    return name && name !== id ? `${name}（${id}）` : id || name;
};
// 上传接口 uri=绝对地址,url=相对路径(uploads/...).预览必须用绝对地址,否则缩略图裂图
const resolveUploadPayload = (event: any): any => {
    const res = event?.response ?? event;
    if (!res) return {};
    const parsed =
        typeof res === "string"
            ? (() => {
                  try {
                      return JSON.parse(res);
                  } catch {
                      return {};
                  }
              })()
            : res;
    return parsed?.data && typeof parsed.data === "object" ? parsed.data : parsed;
};
const pickMediaUrl = (raw: any): string => {
    if (raw == null) return "";
    if (typeof raw === "string") return raw.trim();
    const candidates = [raw.uri, raw.full_url, raw.url, raw.cover_url, raw.pic, raw.thumbnail_path, raw.thumbnail_url];
    const abs = candidates.find((v) => /^https?:\/\//i.test(String(v || "").trim()) || String(v || "").startsWith("//"));
    if (abs) return String(abs).trim();
    return String(candidates.find((v) => String(v || "").trim()) || "").trim();
};
const pushAiImage = (url: string) => {
    const src = url.trim();
    if (!src || aiForm.images.length >= 9 || aiForm.images.includes(src)) return;
    aiForm.images.push(src);
};
// ---- 视频素材选择(对齐数字人侧:本地上传 / 素材库 / 创作库) ----
const aiVideo = ref<{ url: string; cover: string; title: string } | null>(null);
// 视频发布的标题/文案:可手填,也可从文章一键带入
const aiVideoFromContent = ref<number | null>(null);
const fillVideoCopyFromContent = (cid: number | null) => {
    const c = contents.value.find((x) => x.id === cid);
    if (!c) return;
    aiForm.videoTitle = clampAiTitle(String(c.title || ""));
    aiForm.videoDesc = mdToPlain(c.body);
};
const showAiChooseMaterial = ref(false);
const showAiChooseHistory = ref(false);
const aiChooseMaterialRef = ref();
const aiChooseHistoryRef = ref();
const onAiMaterialAction = async (action: any) => {
    if (action.type === "library-video") {
        showAiVideoMenu.value = false;
        showAiChooseMaterial.value = true;
        await nextTick();
        aiChooseMaterialRef.value?.open();
    } else if (action.type === "history") {
        showAiVideoMenu.value = false;
        showAiChooseHistory.value = true;
        await nextTick();
        aiChooseHistoryRef.value?.open();
    } else if (action.type === "upload-video") {
        const data = resolveUploadPayload(action.event);
        const url = pickMediaUrl(data);
        if (url) {
            aiVideo.value = {
                url,
                cover: pickMediaUrl({
                    uri: data.thumbnail_path || data.thumbnail_url || data.pic || data.thumbnail,
                    url: data.thumbnail_url || data.pic,
                }),
                title: String(data.name || ""),
            };
        }
    }
};
const onAiPickMaterial = (list: any[]) => {
    const item = Array.isArray(list) ? list[0] : null;
    if (item) {
        const url = pickMediaUrl(item);
        if (!url) return;
        // title 仅用于素材卡预览展示,不预填发布标题(素材库文件名多为随机哈希)
        aiVideo.value = { url, cover: pickMediaUrl({ uri: item.pic, url: item.pic }), title: String(item.name || item.title || "") };
    }
    showAiChooseMaterial.value = false;
    showAiChooseHistory.value = false;
};
// ---- 图文配图:素材库/创作库/本地上传(与视频同一套菜单,type=image 多选) ----
const showAiImgMenu = ref(false);
const showAiVideoMenu = ref(false);
const showAiImgMaterial = ref(false);
const showAiImgHistory = ref(false);
const aiImgMaterialRef = ref();
const aiImgHistoryRef = ref();
const onAiImageMaterialAction = async (action: any) => {
    if (action.type === "library-image") {
        showAiImgMenu.value = false;
        showAiImgMaterial.value = true;
        await nextTick();
        aiImgMaterialRef.value?.open();
    } else if (action.type === "history") {
        showAiImgMenu.value = false;
        showAiImgHistory.value = true;
        await nextTick();
        aiImgHistoryRef.value?.open();
    } else if (action.type === "upload-image") {
        pushAiImage(pickMediaUrl(resolveUploadPayload(action.event)));
    }
};
const onAiPickImages = (list: any[]) => {
    for (const item of Array.isArray(list) ? list : []) {
        pushAiImage(pickMediaUrl(item));
    }
    showAiImgMaterial.value = false;
    showAiImgHistory.value = false;
};
const openAiPublish = async (row: any) => {
    aiMedia.value = row;
    const forms = mediaForms(row);
    aiForm.type = forms.includes("article") ? "article" : "video";
    aiForm.account = "";
    aiForm.contentId = contents.value[0]?.id ?? null;
    aiForm.videoId = null;
    aiVideo.value = null;
    aiForm.videoTitle = "";
    aiForm.videoDesc = "";
    aiVideoFromContent.value = null;
    aiForm.images = [];
    showAiImgMenu.value = false;
    showAiVideoMenu.value = false;
    showAiPublish.value = true;
    try {
        // alllists 返回分页对象 {lists, page_no, page_size},必须解构;默认每页 25 条,
        // 传大 page_size 防截断;按当前平台类型下发,服务端先过滤一轮
        const res: any = await getPublishAccountList({
            type: AI_APP_TYPE[row.provider_code] || undefined,
            page_size: 999,
        });
        aiAllAccounts.value = res?.lists || (Array.isArray(res) ? res : []);
    } catch (e) {
        aiAllAccounts.value = [];
    }
    fillAiCover();
};

// 图文投稿自动带上文章生成时那张 AI 封面图 —— 否则用户每次都要手动传图。
// 还在出图中就轮询几次；仍拿不到就留空，由用户自己上传。
const coverLoading = ref(false);
let coverTimer: any = null;
const fillAiCover = async () => {
    if (coverTimer) {
        clearTimeout(coverTimer);
        coverTimer = null;
    }
    if (aiForm.type !== "article" || !aiForm.contentId) return;
    const cid = aiForm.contentId;
    const local = contents.value.find((x) => x.id === cid);
    if (local?.cover_url) {
        aiForm.images = [pickMediaUrl(local.cover_url)].filter(Boolean);
        return;
    }
    coverLoading.value = true;
    let tries = 0;
    const poll = async () => {
        if (!showAiPublish.value || aiForm.contentId !== cid) {
            coverLoading.value = false;
            return;
        }
        try {
            const r: any = await geoContentCover(cid);
            if (r?.cover_url) {
                if (aiForm.contentId === cid && !aiForm.images.length) {
                    const cover = pickMediaUrl(r.cover_url);
                    if (cover) aiForm.images = [cover];
                }
                if (local) local.cover_url = r.cover_url;
                coverLoading.value = false;
                return;
            }
            if (r?.status !== "pending" || ++tries >= 10) {
                coverLoading.value = false;
                return;
            }
        } catch (e) {
            coverLoading.value = false;
            return;
        }
        coverTimer = setTimeout(poll, 3000);
    };
    poll();
};
watch(
    () => [aiForm.contentId, aiForm.type],
    () => {
        aiForm.images = [];
        fillAiCover();
    },
);
onUnmounted(() => {
    if (coverTimer) clearTimeout(coverTimer);
});

// Markdown 转纯文本文案(社媒正文)
const mdToPlain = (md: string) =>
    String(md || "")
        .replace(/!\[[^\]]*\]\([^)]*\)/g, "")
        .replace(/\[([^\]]*)\]\([^)]*\)/g, "$1")
        .replace(/[#*>`\-]+/g, "")
        .replace(/\n{3,}/g, "\n\n")
        .trim();
const doAiPublish = async () => {
    const appType = AI_APP_TYPE[aiMedia.value?.provider_code];
    const acc = aiAccounts.value.find((a) => a.account === aiForm.account);
    if (!appType || !acc) return ElMessage.warning("请选择发布账号");
    let mediaUrl: any[] = [];
    let title = "";
    let body = "";
    if (aiForm.type === "article") {
        const c = contents.value.find((x) => x.id === aiForm.contentId);
        if (!c) return ElMessage.warning("请选择文章");
        if (!aiForm.images.length) return ElMessage.warning("图文发布需至少上传 1 张配图");
        title = clampAiTitle(String(c.title || ""));
        body = mdToPlain(c.body);
        mediaUrl = [{ url: aiForm.images }];
    } else {
        if (!aiVideo.value?.url) return ElMessage.warning("请先选择视频素材");
        if (!aiForm.videoTitle.trim()) return ElMessage.warning("请填写视频标题");
        title = clampAiTitle(aiForm.videoTitle.trim());
        body = aiForm.videoDesc.trim() || title;
        mediaUrl = [{ url: aiVideo.value.cover ? [aiVideo.value.cover, aiVideo.value.url] : [aiVideo.value.url] }];
    }
    aiBusy.value = true;
    try {
        // 与矩阵「手动发布」同一后端链路:先建素材包,再下发设备发布任务(即时执行)
        const matrixMediaType = aiForm.type === "video" ? 1 : 2; // PublishTaskTypeEnum: 1视频 2图文
        const name = `GEO投稿-${aiMedia.value.name}-${new Date()
            .toLocaleString("zh-CN", { hour12: false })
            .replace(/[^\d]/g, "")
            .slice(0, 12)}`;
        const { id }: any = await addMatrixTask({
            name,
            media_url: mediaUrl,
            copywriting: [{ title: String(title), content: body, topic: [], is_title_show: 1 }],
            media_type: matrixMediaType,
        });
        const today = new Date();
        const dateStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, "0")}-${String(
            today.getDate(),
        ).padStart(2, "0")}`;
        const accounts = [{ type: acc.type, id: acc.id, account: acc.account }];
        const timeConfig = [{ date: dateStr, times: [1] }]; // 1=立即执行槽位
        // 与矩阵立即发布相同:先查出同设备 30 分钟窗内冲突任务,再带 ID 覆盖
        let taskIds: Array<string | number> = [];
        try {
            const checked: any = await checkTaskPublishTime({
                accounts,
                time_config: timeConfig,
                minutes: 30,
            });
            taskIds = checked?.task_ids || [];
        } catch {
            /* 校验失败仍走立即执行,后端会按同设备窗口补覆盖 */
        }
        const dev: any = await publishDeviceTask({
            name,
            matrix_media_setting_id: id,
            time_config: timeConfig,
            accounts,
            publish_frep: 1,
            media_type: matrixMediaType,
            task_type: 3,
            scene: 2,
            data_type: 0,
            poi: "",
            task_exec_type: 1,
            task_ids: taskIds,
        });
        // 挂进媒体库投稿台账,让「发布记录」能统一看到 AI 手机这条投稿(登记失败不影响已下发的发布任务)
        try {
            await geoPublishPhoneRegister({
                project_id: props.pid,
                media_id: aiMedia.value.id,
                media_type: aiForm.type,
                content_id: aiForm.type === "article" ? aiForm.contentId : 0,
                video_id: 0,
                video_url: aiForm.type === "video" ? aiVideo.value?.url || "" : "",
                video_cover: aiForm.type === "video" ? aiVideo.value?.cover || "" : "",
                title,
                account: acc.account,
                publish_setting_id: dev?.id || "",
            });
        } catch (e) {
            /* 台账登记失败不回滚发布任务,发布进度仍可在「矩阵-我的发布」查看 */
        }
        ElMessage.success(`已提交 ${aiMedia.value.name} 发布任务,AI 手机将自动执行,进度见「矩阵-我的发布」`);
        showAiPublish.value = false;
        loadRecords();
    } catch (e) {
        ElMessage.error(errText(e));
    } finally {
        aiBusy.value = false;
    }
};
// 文章列表「发布」按钮 → 打开媒体投稿(预选该文章)
const openPublish = (row: any) => {
    pubContentId.value = row.id;
    tab.value = "media";
    loadMedia();
    emit("go", "publish");
    ElMessage.info("请选择媒体后点击「投稿」");
};

onMounted(async () => {
    try {
        topics.value = ((await geoTopics(props.pid)) as any)?.list || [];
    } catch (e) {
        /* 忽略 */
    }
    loadContents();
});
</script>

<style lang="scss" scoped>
.media-table {
    :deep(.el-table__cell) {
        padding-top: 14px;
        padding-bottom: 14px;
        vertical-align: middle;
    }
}

.media-chip {
    @apply inline-flex items-center h-6 px-2 rounded-md text-xs text-slate-600 bg-[#F1F5F9];
}
.media-chip--ok {
    @apply text-emerald-700 bg-emerald-50;
}
.media-chip--warn {
    @apply text-amber-700 bg-amber-50;
}
.media-chip--mute {
    @apply text-slate-500 bg-[#F1F5F9];
}
.media-chip--ai {
    @apply text-primary bg-[#F5F7FF];
}
.media-chip--danger {
    @apply text-rose-700 bg-rose-50;
}

.rec-btns {
    @apply inline-flex items-center overflow-hidden;
    height: 32px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
}

.rec-btn {
    @apply h-8 px-3 text-xs text-slate-600 bg-white;
    border-right: 1px solid #e2e8f0;
    transition: background-color 150ms ease, color 150ms ease;
    &:last-child {
        border-right: none;
    }
    &:hover:not(:disabled) {
        background: #f8fafc;
        color: var(--el-color-primary);
    }
    &--danger:hover:not(:disabled) {
        color: var(--el-color-danger);
        background: #fef2f2;
    }
}

@media (prefers-reduced-motion: reduce) {
    .rec-btn {
        transition: none;
    }
}
</style>
