<template>
    <div
        class="font-sans text-[#111827] flex flex-col gap-3"
        style="font-family: -apple-system, 'PingFang SC', 'Microsoft YaHei', sans-serif">
        <div
            class="flex items-start gap-3 px-4 py-[11px] bg-[#fffbeb] border border-[#fde68a] border-l-[3px] border-l-[#f59e0b] rounded-[10px] text-[13px] text-[#78350f]">
            <div class="text-[15px] mt-[1px] shrink-0">⚡</div>
            <div class="flex items-start gap-2">
                <span class="font-bold text-[#b45309] mr-2">操作须知</span>
                <span class="flex flex-wrap items-center gap-x-0 gap-y-0">
                    <span>更新前务必备份系统与数据库</span>
                    <span class="mx-2 text-[#d1a24b]">·</span>
                    <span>文件同步不删除本地文件</span>
                    <span class="mx-2 text-[#d1a24b]">·</span>
                    <span>数据库同步仅新增，<b class="text-[#dc2626] font-bold">绝不删除数据</b></span>
                    <span class="mx-2 text-[#d1a24b]">·</span>
                    <span>二次开发请谨慎使用文件同步，建议手动合并</span>
                </span>
            </div>
        </div>

        <div
            class="flex items-center justify-between flex-wrap gap-3 px-5 py-[14px] bg-white border border-[#e5e7eb] rounded-[10px] shadow-[0_1px_3px_rgba(0,0,0,0.07),0_1px_2px_rgba(0,0,0,0.04)]">
            <div class="flex items-center gap-[18px]">
                <div class="flex flex-col gap-[2px]">
                    <span class="text-[10.5px] text-[#6b7280] uppercase tracking-[0.06em]">本地版本</span>
                    <span class="text-[18px] font-extrabold tracking-tight text-[#111827]">{{
                        getSystemVersion || "—"
                    }}</span>
                </div>
                <div class="text-[#d1d5db]">
                    <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                        <path
                            d="M4 10h12M12 6l4 4-4 4"
                            stroke="currentColor"
                            stroke-width="1.5"
                            stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </div>
                <div class="flex flex-col gap-[2px]">
                    <span class="text-[10.5px] text-[#6b7280] uppercase tracking-[0.06em]">远端版本</span>
                    <span v-if="versionInfo.loading" class="text-[14px] text-[#6b7280] font-normal">获取中…</span>
                    <span
                        v-else-if="versionInfo.remoteName"
                        class="text-[18px] font-extrabold tracking-tight text-[#3b82f6]">
                        {{ versionInfo.remoteName }}
                    </span>
                    <span v-else class="text-[13px] text-[#dc2626] font-extrabold">获取失败</span>
                </div>
                <div
                    v-if="versionInfo.remoteName"
                    class="px-[11px] py-[3px] rounded-full text-[12px] font-semibold"
                    :class="
                        versionInfo.upToDate
                            ? 'bg-[#f0fdf4] text-[#16a34a] border border-[#bbf7d0]'
                            : 'bg-[#fffbeb] text-[#d97706] border border-[#fde68a]'
                    ">
                    <span v-if="versionInfo.upToDate">✓ 已是最新</span>
                    <span v-else>↑ 可升级</span>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button
                    class="flex items-center gap-[7px] px-4 py-[7px] rounded-[7px] bg-[#f8fafc] border border-[#e5e7eb] text-[#6b7280] text-[13px] font-medium cursor-pointer transition-all hover:bg-[#f1f5f9] hover:text-[#111827]"
                    @click="downloadSourceCode">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" style="flex-shrink: 0">
                        <path
                            d="M7 1v8M4 7l3 3 3-3M2 11h10"
                            stroke="currentColor"
                            stroke-width="1.5"
                            stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                    下载源码包
                </button>
                <button
                    class="flex items-center gap-[7px] px-4 py-[7px] rounded-[7px] bg-[#f8fafc] border border-[#e5e7eb] text-[#6b7280] text-[13px] font-medium cursor-pointer transition-all hover:bg-[#f1f5f9] hover:text-[#111827]"
                    @click="handleDownloadMiniProgramPackage">
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none" style="flex-shrink: 0">
                        <path
                            d="M7 1v8M4 7l3 3 3-3M2 11h10"
                            stroke="currentColor"
                            stroke-width="1.5"
                            stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                    下载小程序包
                </button>
            </div>
        </div>

        <div
            v-if="!versionInfo.upToDate && (notice.loading || notice.list.length > 0 || notice.error)"
            class="bg-white border border-[#e5e7eb] rounded-[10px] shadow-[0_1px_3px_rgba(0,0,0,0.07)] px-5 py-4 flex flex-col gap-3">
            <div class="flex items-center gap-2">
                <span class="w-[3px] h-[16px] rounded-full bg-[#8b5cf6] shrink-0"></span>
                <span class="text-[14px] font-bold text-[#111827]">更新日志</span>
                <span v-if="notice.loading" class="ml-auto text-[12px] text-[#6b7280]">加载中…</span>
                <span v-else-if="notice.error" class="ml-auto text-[12px] text-[#dc2626]">加载失败</span>
                <span v-else class="ml-auto text-[11px] text-[#9ca3af]">{{ notice.list.length }} 个版本</span>
            </div>

            <div v-if="notice.loading" class="flex flex-col gap-2">
                <div v-for="i in 3" :key="i" class="h-[42px] bg-[#f3f4f6] rounded-[7px] animate-pulse"></div>
            </div>

            <div v-else-if="notice.error" class="text-[13px] text-[#dc2626] bg-[#fef2f2] px-4 py-3 rounded-[7px]">
                ⚠️ 更新日志加载失败，请检查远端连接
            </div>

            <div v-else class="flex flex-col gap-[6px]">
                <div
                    v-for="log in notice.list"
                    :key="log.version"
                    class="border border-[#e5e7eb] rounded-[8px] overflow-hidden">
                    <button
                        class="w-full flex items-center gap-[10px] px-[14px] py-[10px] text-left cursor-pointer transition-colors hover:bg-[#f8fafc] border-none"
                        :class="notice.expanded === log.version ? 'bg-[#f8fafc]' : 'bg-white'"
                        @click="notice.expanded = notice.expanded === log.version ? null : log.version">
                        <span
                            class="px-[8px] py-[2px] rounded-full text-[10px] font-bold uppercase shrink-0"
                            :class="{
                                'bg-[#dbeafe] text-[#1e40af]': log.type === 'feature',
                                'bg-[#dcfce7] text-[#166534]': log.type === 'bugfix',
                                'bg-[#fee2e2] text-[#991b1b]': log.type === 'security',
                            }">
                            {{ log.type === "feature" ? "新功能" : log.type === "bugfix" ? "修复" : "安全" }}
                        </span>
                        <span class="text-[13px] font-bold text-[#111827]">{{ log.version }}</span>
                        <span class="text-[12.5px] text-[#6b7280] flex-1 truncate">{{ log.title }}</span>
                        <span class="text-[11px] text-[#9ca3af] shrink-0 hidden sm:block">{{ log.date }}</span>
                        <span class="text-[11px] text-[#9ca3af] shrink-0 bg-[#f3f4f6] px-[6px] py-[1px] rounded-[4px]">
                            {{ log.items.length }} 项
                        </span>
                        <svg
                            width="14"
                            height="14"
                            viewBox="0 0 14 14"
                            fill="none"
                            class="shrink-0 transition-transform text-[#9ca3af]"
                            :class="notice.expanded === log.version ? 'rotate-180' : ''">
                            <path
                                d="M3 5l4 4 4-4"
                                stroke="currentColor"
                                stroke-width="1.5"
                                stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                    </button>
                    <div
                        v-if="notice.expanded === log.version"
                        class="border-t border-[#f0f0f0] bg-[#fafafa] px-[14px] py-[10px]">
                        <ul class="flex flex-col gap-[5px]">
                            <li
                                v-for="(item, idx) in log.items"
                                :key="idx"
                                class="notice-item flex items-start gap-[8px] text-[12.5px] text-[#374151]">
                                <span class="w-[5px] h-[5px] rounded-full bg-[#8b5cf6] shrink-0 mt-[5px]"></span>
                                <span v-html="item"></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="bg-white border border-[#e5e7eb] rounded-[10px] shadow-[0_1px_3px_rgba(0,0,0,0.07),0_1px_2px_rgba(0,0,0,0.04)] p-5 flex flex-col gap-5">
            <div class="flex flex-wrap gap-[10px] pb-5 border-b border-[#e5e7eb]">
                <button
                    class="inline-flex items-center gap-2 px-5 py-[10px] rounded-[8px] text-[14px] font-semibold border-none cursor-pointer transition-all tracking-tight bg-[#3b82f6] text-white hover:bg-[#2563eb] disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="detecting || oneKey.running || file.syncing || db.executing || sql.batchRunning"
                    @click="onDetectAll">
                    <span class="flex items-center justify-center w-[17px] h-[17px]">
                        <svg v-if="!detecting" width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <circle cx="7" cy="7" r="5" stroke="currentColor" stroke-width="1.5" />
                            <path d="M11 11l3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                        </svg>
                        <span v-else class="spin-ring"></span>
                    </span>
                    <span v-if="!detecting">检测差异</span>
                    <span v-else-if="detectStep === 'file'">检测文件中…</span>
                    <span v-else-if="detectStep === 'db'">检测数据库中…</span>
                    <span v-else-if="detectStep === 'sql'">检测 SQL 版本…</span>
                    <span v-else>检测中…</span>
                </button>

                <button
                    v-if="detected && hasAnyPending"
                    class="inline-flex items-center gap-2 px-5 py-[10px] rounded-[8px] text-[14px] font-semibold border-none cursor-pointer transition-all tracking-tight bg-[#dc2626] text-white hover:bg-[#b91c1c] disabled:opacity-50 disabled:cursor-not-allowed"
                    :disabled="detecting || oneKey.running || file.syncing || db.executing || sql.batchRunning"
                    @click="onOneKeyUpdateConfirm">
                    <span class="flex items-center justify-center w-[17px] h-[17px]">
                        <svg v-if="!oneKey.running" width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path
                                d="M8 2l1.5 4h4l-3 2.5 1 4L8 10l-3.5 2.5 1-4L2.5 6H6.5L8 2z"
                                stroke="currentColor"
                                stroke-width="1.3"
                                stroke-linejoin="round" />
                        </svg>
                        <span v-else class="spin-ring"></span>
                    </span>
                    {{ oneKey.running ? "更新中…" : "一键更新" }}
                </button>

                <button
                    v-if="showVersionSaveBtn"
                    class="inline-flex items-center gap-2 px-5 py-[10px] rounded-[8px] text-[14px] font-semibold border-none cursor-pointer transition-all tracking-tight text-white disabled:opacity-50 disabled:cursor-not-allowed"
                    :class="hasAnyStepError ? 'bg-[#f59e0b] hover:bg-[#d97706]' : 'bg-[#0891b2] hover:bg-[#0e7490]'"
                    :disabled="versionInfo.saving"
                    @click="onVersionSave">
                    <span class="flex items-center justify-center w-[17px] h-[17px]">
                        <span v-if="versionInfo.saving" class="spin-ring"></span>
                        <svg v-else-if="hasAnyStepError" width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.3" />
                            <path d="M8 4v5M8 11v1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                        </svg>
                        <svg v-else width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path
                                d="M3 8.5l3.5 3.5 7-7"
                                stroke="currentColor"
                                stroke-width="1.8"
                                stroke-linecap="round"
                                stroke-linejoin="round" />
                        </svg>
                    </span>
                    {{ versionInfo.saving ? "写入中…" : hasAnyStepError ? "忽略错误并写入版本号" : "完成更新" }}
                </button>
            </div>

            <div v-if="detecting" class="bg-[#f8fafc] border border-[#e5e7eb] rounded-[8px] px-5 py-[14px]">
                <div class="flex items-center">
                    <template v-for="(step, idx) in detectSteps" :key="step.key">
                        <div class="flex items-center gap-2">
                            <div
                                class="w-[26px] h-[26px] rounded-full flex items-center justify-center text-[11px] font-bold shrink-0 transition-all"
                                :class="
                                    detectStepIndex > idx
                                        ? 'bg-[#16a34a] text-white'
                                        : detectStep === step.key
                                        ? 'bg-[#3b82f6] text-white'
                                        : 'bg-[#e5e7eb] text-[#9ca3af]'
                                ">
                                <span v-if="detectStepIndex > idx">✓</span>
                                <span v-else-if="detectStep === step.key" class="spin-ring sm"></span>
                                <span v-else>{{ idx + 1 }}</span>
                            </div>
                            <span
                                class="text-[13px] font-medium whitespace-nowrap transition-colors"
                                :class="
                                    detectStepIndex > idx
                                        ? 'text-[#16a34a]'
                                        : detectStep === step.key
                                        ? 'text-[#3b82f6]'
                                        : 'text-[#9ca3af]'
                                ">
                                {{ step.label }}
                            </span>
                        </div>
                        <div
                            v-if="idx < detectSteps.length - 1"
                            class="flex-1 h-[2px] mx-[10px] min-w-[20px] transition-colors"
                            :class="detectStepIndex > idx ? 'bg-[#3b82f6]' : 'bg-[#e5e7eb]'"></div>
                    </template>
                </div>
            </div>

            <div v-if="oneKey.running" class="bg-[#f8fafc] border border-[#e5e7eb] rounded-[8px] px-5 py-[14px]">
                <div class="text-[11px] font-bold text-[#6b7280] uppercase tracking-[0.07em] mb-3">一键更新进度</div>
                <div class="flex items-center">
                    <template v-for="(step, idx) in oneKeySteps" :key="step.key">
                        <div class="flex items-center gap-2">
                            <div
                                class="w-[26px] h-[26px] rounded-full flex items-center justify-center text-[11px] font-bold shrink-0 transition-all"
                                :class="
                                    oneKey.stepStatus[step.key] === 'success'
                                        ? 'bg-[#16a34a] text-white'
                                        : oneKey.stepStatus[step.key] === 'error'
                                        ? 'bg-[#dc2626] text-white'
                                        : oneKey.currentStep === step.key
                                        ? 'bg-[#3b82f6] text-white'
                                        : 'bg-[#e5e7eb] text-[#9ca3af]'
                                ">
                                <span v-if="oneKey.stepStatus[step.key] === 'success'">✓</span>
                                <span v-else-if="oneKey.stepStatus[step.key] === 'error'">✗</span>
                                <span v-else-if="oneKey.currentStep === step.key" class="spin-ring sm"></span>
                                <span v-else>{{ idx + 1 }}</span>
                            </div>
                            <span
                                class="text-[13px] font-medium whitespace-nowrap transition-colors"
                                :class="
                                    oneKey.stepStatus[step.key] === 'success'
                                        ? 'text-[#16a34a]'
                                        : oneKey.stepStatus[step.key] === 'error'
                                        ? 'text-[#dc2626]'
                                        : oneKey.currentStep === step.key
                                        ? 'text-[#3b82f6]'
                                        : 'text-[#9ca3af]'
                                ">
                                {{ step.label }}
                            </span>
                        </div>
                        <div
                            v-if="idx < oneKeySteps.length - 1"
                            class="flex-1 h-[2px] mx-[10px] min-w-[20px] transition-colors"
                            :class="oneKeyStepDone(idx) ? 'bg-[#3b82f6]' : 'bg-[#e5e7eb]'"></div>
                    </template>
                </div>
            </div>

            <div v-if="detected && !detecting" class="flex flex-wrap items-center gap-2">
                <div
                    class="inline-flex items-center gap-[6px] px-[14px] py-[6px] rounded-[8px] text-[13px] border"
                    :class="
                        file.tasks.length > 0
                            ? 'bg-[#fffbeb] border-[#fde68a] text-[#d97706]'
                            : 'bg-[#f0fdf4] border-[#bbf7d0] text-[#16a34a]'
                    ">
                    <span>📁</span><span>文件差异</span>
                    <strong class="font-bold">{{ file.tasks.length > 0 ? `${file.tasks.length} 个` : "无 ✓" }}</strong>
                </div>
                <!-- 覆盖目录变更 badge -->
                <template v-for="ow in overwrite.dirs" :key="ow.dir">
                    <div
                        class="inline-flex items-center gap-[6px] px-[14px] py-[6px] rounded-[8px] text-[13px] border"
                        :class="
                            ow.has_update
                                ? 'bg-[#fff7ed] border-[#fed7aa] text-[#ea580c]'
                                : 'bg-[#f0fdf4] border-[#bbf7d0] text-[#16a34a]'
                        ">
                        <span>🗂️</span>
                        <span class="max-w-[130px] truncate" :title="ow.dir">{{ ow.dir }}</span>
                        <strong class="font-bold shrink-0">
                            {{ ow.has_update ? `${ow.changed} 个变更` : "无变更 ✓" }}
                        </strong>
                    </div>
                </template>

                <div
                    class="inline-flex items-center gap-[6px] px-[14px] py-[6px] rounded-[8px] text-[13px] border"
                    :class="
                        pendingDiffs.length > 0
                            ? 'bg-[#fffbeb] border-[#fde68a] text-[#d97706]'
                            : 'bg-[#f0fdf4] border-[#bbf7d0] text-[#16a34a]'
                    ">
                    <span>🗄️</span><span>数据库差异</span>
                    <strong class="font-bold">{{
                        pendingDiffs.length > 0 ? `${pendingDiffs.length} 项` : "无 ✓"
                    }}</strong>
                </div>
                <div
                    class="inline-flex items-center gap-[6px] px-[14px] py-[6px] rounded-[8px] text-[13px] border"
                    :class="
                        pendingSqlTasks.length > 0
                            ? 'bg-[#f5f3ff] border-[#c4b5fd] text-[#7c3aed]'
                            : 'bg-[#f0fdf4] border-[#bbf7d0] text-[#16a34a]'
                    ">
                    <span>📜</span><span>SQL 版本</span>
                    <strong class="font-bold">{{
                        pendingSqlTasks.length > 0 ? `${pendingSqlTasks.length} 待执行` : "最新 ✓"
                    }}</strong>
                </div>
                <div
                    v-if="
                        file.tasks.length === 0 &&
                        overwrite.dirs.every((d) => !d.has_update) &&
                        pendingDiffs.length === 0 &&
                        pendingSqlTasks.length === 0
                    "
                    class="text-[13px] font-semibold text-[#16a34a]">
                    🎉 系统已是最新版本，无需更新
                </div>
            </div>
            <!-- 覆盖目录变更文件列表 -->
            <div
                v-if="detected && !detecting && overwrite.dirs.some((d) => d.has_update)"
                class="flex flex-col gap-[10px]">
                <div class="flex items-center gap-2 text-[14px] font-bold text-[#111827]">
                    <span class="w-[3px] h-[16px] rounded-full bg-[#ea580c] shrink-0"></span>
                    发现
                    <em class="not-italic text-[#ea580c]">
                        {{ overwrite.dirs.filter((d) => d.has_update).length }}
                    </em>
                    个目录有变更
                </div>

                <div
                    v-for="ow in overwrite.dirs.filter((d) => d.has_update)"
                    :key="ow.dir"
                    class="border border-[#fed7aa] rounded-[8px] overflow-hidden">
                    <!-- 目录标题行 -->
                    <div class="flex items-center gap-3 px-[14px] py-[10px] bg-[#fff7ed] border-b border-[#fed7aa]">
                        <span class="text-[13px] font-bold text-[#ea580c] font-mono flex-1">{{ ow.dir }}</span>
                        <span class="text-[11px] text-[#9ca3af] bg-[#f3f4f6] px-[6px] py-[1px] rounded-[4px] shrink-0">
                            {{ ow.changed }} / {{ ow.total }} 个文件变更
                        </span>
                        <span class="text-[11px] text-[#9ca3af] shrink-0">{{ formatSize(ow.size) }}</span>
                        <!-- 单目录覆盖按钮 -->
                        <span
                            v-if="overwrite.dirStatus[ow.dir] === 'success'"
                            class="text-[11px] font-bold text-[#16a34a] whitespace-nowrap shrink-0">
                            ✓ 已覆盖
                        </span>
                        <template v-else-if="overwrite.dirStatus[ow.dir] === 'error'">
                            <span class="text-[11px] font-bold text-[#dc2626] whitespace-nowrap shrink-0">
                                ✗ 覆盖失败
                            </span>
                            <button
                                class="mini-btn bg-[#dc2626] text-white border-[#dc2626] hover:bg-[#b91c1c] shrink-0"
                                :disabled="oneKey.running || overwrite.syncing"
                                @click="onSingleDirOverwrite(ow)">
                                <span>↺</span> 重新覆盖
                            </button>
                        </template>
                        <button
                            v-else
                            class="mini-btn bg-[#ea580c] text-white border-[#ea580c] hover:bg-[#c2410c] shrink-0"
                            :disabled="oneKey.running || overwrite.syncing || overwrite.dirStatus[ow.dir] === 'running'"
                            @click="onSingleDirOverwrite(ow)">
                            <span
                                v-if="overwrite.dirStatus[ow.dir] === 'running'"
                                class="spin-ring xs"
                                style="border-color: rgba(255, 255, 255, 0.3); border-top-color: #fff"></span>
                            <span v-else>↑</span>
                            {{ overwrite.dirStatus[ow.dir] === "running" ? "覆盖中…" : "覆盖此目录" }}
                        </button>
                    </div>
                    <!-- 变更文件列表 -->
                    <div class="max-h-[200px] overflow-y-auto bg-[#fffbf5]">
                        <div
                            v-for="f in ow.files"
                            :key="f.file"
                            class="flex items-center gap-2 px-[14px] py-[7px] border-b border-[#fef3e2] last:border-b-0 text-[12px] font-mono">
                            <span
                                class="px-[6px] py-[1px] rounded-[4px] text-[10px] font-bold uppercase shrink-0"
                                :class="
                                    f.type === 'new' ? 'bg-[#dcfce7] text-[#166534]' : 'bg-[#fef9c3] text-[#92400e]'
                                ">
                                {{ f.type === "new" ? "新增" : "修改" }}
                            </span>
                            <span class="flex-1 text-[#374151] truncate" :title="f.file">{{ f.file }}</span>
                            <span
                                v-if="f.size"
                                class="text-[#9ca3af] text-[11px] shrink-0 bg-[#f3f4f6] px-[6px] py-[1px] rounded-[4px]">
                                {{ formatSize(f.size) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 覆盖目录同步进度 -->
            <div
                v-if="overwrite.syncing || overwrite.syncDone"
                class="bg-[#f8fafc] border border-[#e5e7eb] rounded-[8px] px-4 py-[13px]">
                <div class="flex items-center gap-2 mb-[5px]">
                    <span class="w-[7px] h-[7px] rounded-full bg-[#ea580c] shrink-0"></span>
                    <span class="text-[13px] font-bold text-[#111827] flex-1">目录覆盖进度</span>
                    <span class="text-[12px] text-[#ea580c] font-semibold tabular-nums">
                        {{ overwrite.progressDone }} / {{ overwrite.progressTotal }}
                    </span>
                </div>
                <div class="text-[12px] text-[#6b7280] mb-2 min-h-[15px]">{{ overwrite.progressLabel }}</div>
                <!-- 各目录状态小标签 -->
                <div class="flex flex-wrap gap-2 mb-2">
                    <div
                        v-for="ow in overwrite.dirs.filter((d) => d.has_update)"
                        :key="ow.dir"
                        class="inline-flex items-center gap-[5px] px-[10px] py-[3px] rounded-full text-[11px] font-semibold border"
                        :class="
                            overwrite.dirStatus[ow.dir] === 'success'
                                ? 'bg-[#f0fdf4] border-[#bbf7d0] text-[#16a34a]'
                                : overwrite.dirStatus[ow.dir] === 'error'
                                ? 'bg-[#fef2f2] border-[#fca5a5] text-[#dc2626]'
                                : overwrite.dirStatus[ow.dir] === 'running'
                                ? 'bg-[#eff6ff] border-[#bfdbfe] text-[#3b82f6]'
                                : 'bg-[#f3f4f6] border-[#e5e7eb] text-[#9ca3af]'
                        ">
                        <span v-if="overwrite.dirStatus[ow.dir] === 'success'">✓</span>
                        <span v-else-if="overwrite.dirStatus[ow.dir] === 'error'">✗</span>
                        <span
                            v-else-if="overwrite.dirStatus[ow.dir] === 'running'"
                            class="spin-ring xs"
                            style="border-color: rgba(59, 130, 246, 0.3); border-top-color: #3b82f6"></span>
                        <span v-else>○</span>
                        <span class="max-w-[110px] truncate" :title="ow.dir">{{ ow.dir }}</span>
                    </div>
                </div>
                <div class="h-[5px] bg-[#e5e7eb] rounded-full overflow-hidden">
                    <div
                        class="h-full rounded-full transition-[width] duration-300 ease-out"
                        :class="
                            overwrite.progressStatus === 'success'
                                ? 'bg-[#16a34a]'
                                : overwrite.progressStatus === 'exception'
                                ? 'bg-[#dc2626]'
                                : 'bg-[#ea580c] progress-stripe'
                        "
                        :style="{ width: overwrite.progressPercent + '%' }"></div>
                </div>
            </div>

            <div
                v-if="file.syncing || file.syncDone"
                class="bg-[#f8fafc] border border-[#e5e7eb] rounded-[8px] px-4 py-[13px]">
                <div class="flex items-center gap-2 mb-[5px]">
                    <span class="w-[7px] h-[7px] rounded-full bg-[#3b82f6] shrink-0"></span>
                    <span class="text-[13px] font-bold text-[#111827] flex-1">文件同步进度</span>
                    <span class="text-[12px] text-[#3b82f6] font-semibold tabular-nums"
                        >{{ file.progressDone }} / {{ file.progressTotal }}</span
                    >
                </div>
                <div class="text-[12px] text-[#6b7280] mb-2 min-h-[15px]">{{ file.progressLabel }}</div>
                <div class="h-[5px] bg-[#e5e7eb] rounded-full overflow-hidden">
                    <div
                        class="h-full rounded-full transition-[width] duration-300 ease-out"
                        :class="
                            file.progressStatus === 'success'
                                ? 'bg-[#16a34a]'
                                : file.progressStatus === 'exception'
                                ? 'bg-[#dc2626]'
                                : 'bg-[#3b82f6] progress-stripe'
                        "
                        :style="{ width: file.progressPercent + '%' }"></div>
                </div>
            </div>

            <div
                v-if="db.executing || db.execDone"
                class="bg-[#f8fafc] border border-[#e5e7eb] rounded-[8px] px-4 py-[13px]">
                <div class="flex items-center gap-2 mb-[5px]">
                    <span class="w-[7px] h-[7px] rounded-full bg-[#d97706] shrink-0"></span>
                    <span class="text-[13px] font-bold text-[#111827] flex-1">数据库升级进度</span>
                    <span class="text-[12px] text-[#3b82f6] font-semibold tabular-nums"
                        >{{ db.progressDone }} / {{ db.executingTotal }}</span
                    >
                </div>
                <div class="text-[12px] text-[#6b7280] mb-2 min-h-[15px]">{{ db.progressLabel }}</div>
                <div class="h-[5px] bg-[#e5e7eb] rounded-full overflow-hidden">
                    <div
                        class="h-full rounded-full transition-[width] duration-300 ease-out"
                        :class="
                            db.progressStatus === 'success'
                                ? 'bg-[#16a34a]'
                                : db.progressStatus === 'exception'
                                ? 'bg-[#dc2626]'
                                : 'bg-[#d97706] progress-stripe'
                        "
                        :style="{ width: db.progressPercent + '%' }"></div>
                </div>
            </div>

            <div
                v-if="sql.batchRunning || sql.batchDone"
                class="bg-[#f8fafc] border border-[#e5e7eb] rounded-[8px] px-4 py-[13px]">
                <div class="flex items-center gap-2 mb-[5px]">
                    <span class="w-[7px] h-[7px] rounded-full bg-[#7c3aed] shrink-0"></span>
                    <span class="text-[13px] font-bold text-[#111827] flex-1">SQL 版本升级进度</span>
                    <span class="text-[12px] text-[#3b82f6] font-semibold tabular-nums"
                        >{{ sql.progressDone }} / {{ sql.progressTotal }}</span
                    >
                </div>
                <div class="text-[12px] text-[#6b7280] mb-2 min-h-[15px]">{{ sql.progressLabel }}</div>
                <div class="h-[5px] bg-[#e5e7eb] rounded-full overflow-hidden">
                    <div
                        class="h-full rounded-full transition-[width] duration-300 ease-out"
                        :class="
                            sql.progressStatus === 'success'
                                ? 'bg-[#16a34a]'
                                : sql.progressStatus === 'exception'
                                ? 'bg-[#dc2626]'
                                : 'bg-[#7c3aed] progress-stripe'
                        "
                        :style="{ width: sql.progressPercent + '%' }"></div>
                </div>
            </div>

            <div v-if="file.tasks.length > 0" class="flex flex-col gap-[10px]">
                <div class="flex flex-wrap items-center justify-between gap-[10px]">
                    <div class="flex items-center gap-2 text-[14px] font-bold text-[#111827]">
                        <span class="w-[3px] h-[16px] rounded-full bg-[#3b82f6] shrink-0"></span>
                        发现 <em class="not-italic text-[#3b82f6]">{{ file.tasks.length }}</em> 个差异文件
                    </div>
                    <div class="flex gap-1 flex-wrap">
                        <button
                            v-for="tab in fileTabs"
                            :key="tab.key"
                            class="inline-flex items-center gap-[5px] px-3 py-[5px] rounded-[6px] text-[12px] font-medium border cursor-pointer transition-all"
                            :class="
                                currentFileType === tab.key
                                    ? 'bg-[#3b82f6] border-[#3b82f6] text-white'
                                    : 'bg-[transparent] border-[#e5e7eb] text-[#6b7280] hover:bg-[#f8fafc] hover:text-[#111827]'
                            "
                            @click="currentFileType = tab.key">
                            {{ tab.label }}
                            <span
                                class="min-w-[17px] text-center px-1 rounded-full text-[10px] font-bold"
                                :class="currentFileType === tab.key ? 'bg-white/25' : tab.countClass"
                                >{{ tab.count }}</span
                            >
                        </button>
                    </div>
                </div>
                <div
                    class="border border-[#e5e7eb] rounded-[8px] overflow-hidden max-h-[360px] overflow-y-auto bg-[#f9fafb] scrollbar-thin">
                    <template v-for="group in fileGroupConfig" :key="group.type">
                        <template
                            v-if="
                                (currentFileType === 'all' || currentFileType === group.type) && group.list.length > 0
                            ">
                            <div
                                class="flex items-center gap-2 px-[14px] py-[5px] text-[12px] font-bold sticky top-0 z-[5] border-b border-[#e5e7eb]"
                                :class="group.headerClass">
                                <span
                                    class="w-[6px] h-[6px] rounded-full shrink-0"
                                    :class="group.dotClass"
                                    style="background: currentColor"></span>
                                {{ group.label }}
                                <span class="ml-auto text-[11px] opacity-65">{{ group.list.length }}</span>
                            </div>
                            <div
                                v-for="item in group.list"
                                :key="item.file"
                                class="flex items-center gap-2 px-[14px] py-2 border-b border-[#f0f0f0] last:border-b-0 bg-white hover:bg-[#fafbff] transition-colors text-[12.5px] font-mono"
                                :class="fileRowClass(item.file)">
                                <span
                                    class="px-[7px] py-[2px] rounded-[4px] text-[10px] font-bold uppercase tracking-[0.04em] shrink-0"
                                    :class="group.badgeClass"
                                    >{{ group.tagLabel }}</span
                                >
                                <span
                                    class="flex-1 overflow-hidden text-ellipsis whitespace-nowrap min-w-0 text-[#374151]"
                                    :title="item.file"
                                    >{{ item.file }}</span
                                >
                                <span
                                    v-if="item.size"
                                    class="text-[#9ca3af] text-[11px] shrink-0 bg-[#f3f4f6] px-[6px] py-[1px] rounded-[4px]"
                                    >{{ formatSize(item.size) }}</span
                                >
                                <div class="flex items-center gap-1 shrink-0 ml-auto">
                                    <span
                                        v-if="file.taskStatus[item.file] === 'success'"
                                        class="text-[11px] font-bold text-[#16a34a] whitespace-nowrap"
                                        >✓ 已同步</span
                                    >
                                    <template v-else-if="file.taskStatus[item.file] === 'error'">
                                        <button
                                            v-if="item.type === 'modify'"
                                            class="mini-btn bg-[#e0f2fe] text-[#0369a1] border-[#bae6fd] hover:bg-[#bae6fd]"
                                            :disabled="diffState.loadingFile === item.file"
                                            @click.stop="onViewDiff(item)">
                                            <span v-if="diffState.loadingFile === item.file" class="spin-ring xs"></span
                                            ><span v-else>🔍</span> 详情
                                        </button>
                                        <button
                                            class="mini-btn bg-[#dc2626] text-white border-[#dc2626] hover:bg-[#b91c1c]"
                                            :disabled="oneKey.running || file.syncing || !!file.taskSyncing[item.file]"
                                            @click.stop="onSingleFileSync(item)">
                                            <span v-if="!!file.taskSyncing[item.file]" class="spin-ring xs"></span
                                            ><span v-else>↺</span> 重试
                                        </button>
                                        <button
                                            class="mini-btn bg-[transparent] text-[#9ca3af] border-[#e5e7eb] hover:bg-[#fee2e2] hover:text-[#dc2626] hover:border-[#fca5a5]"
                                            :disabled="oneKey.running || file.syncing"
                                            @click.stop="onRemoveFileTask(item)">
                                            ✕
                                        </button>
                                    </template>
                                    <template v-else>
                                        <button
                                            v-if="item.type === 'modify'"
                                            class="mini-btn bg-[#e0f2fe] text-[#0369a1] border-[#bae6fd] hover:bg-[#bae6fd]"
                                            :disabled="diffState.loadingFile === item.file"
                                            @click.stop="onViewDiff(item)">
                                            <span v-if="diffState.loadingFile === item.file" class="spin-ring xs"></span
                                            ><span v-else>🔍</span> 详情
                                        </button>
                                        <button
                                            class="mini-btn bg-[#3b82f6] text-white border-[#3b82f6] hover:bg-[#2563eb]"
                                            :disabled="oneKey.running || file.syncing || !!file.taskSyncing[item.file]"
                                            @click.stop="onSingleFileSync(item)">
                                            <span v-if="!!file.taskSyncing[item.file]" class="spin-ring xs"></span
                                            ><span v-else>↑</span> 同步
                                        </button>
                                        <button
                                            class="mini-btn bg-[transparent] text-[#9ca3af] border-[#e5e7eb] hover:bg-[#fee2e2] hover:text-[#dc2626] hover:border-[#fca5a5]"
                                            :disabled="oneKey.running || file.syncing"
                                            @click.stop="onRemoveFileTask(item)">
                                            ✕
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </template>
                        <div
                            v-if="currentFileType === group.type && group.list.length === 0"
                            class="p-7 text-center text-[#9ca3af] text-[13px]">
                            暂无{{ group.tagLabel }}文件
                        </div>
                    </template>
                </div>
            </div>

            <!-- 数据库差异列表 -->
            <div v-if="db.diffs.length > 0" class="flex flex-col gap-[10px]">
                <div class="flex flex-wrap items-center justify-between gap-[10px]">
                    <div class="flex items-center gap-2 text-[14px] font-bold text-[#111827]">
                        <span class="w-[3px] h-[16px] rounded-full bg-[#d97706] shrink-0"></span>
                        发现 <em class="not-italic text-[#3b82f6]">{{ db.diffs.length }}</em> 项数据库结构差异
                    </div>
                    <div class="flex gap-1 flex-wrap">
                        <button
                            v-for="tab in dbTabs"
                            :key="tab.key"
                            class="inline-flex items-center gap-[5px] px-3 py-[5px] rounded-[6px] text-[12px] font-medium border cursor-pointer transition-all"
                            :class="
                                currentDbType === tab.key
                                    ? 'bg-[#3b82f6] border-[#3b82f6] text-white'
                                    : 'bg-[transparent] border-[#e5e7eb] text-[#6b7280] hover:bg-[#f8fafc] hover:text-[#111827]'
                            "
                            @click="currentDbType = tab.key">
                            {{ tab.label }}
                            <span
                                class="min-w-[17px] text-center px-1 rounded-full text-[10px] font-bold"
                                :class="currentDbType === tab.key ? 'bg-white/25' : tab.countClass"
                                >{{ tab.count }}</span
                            >
                        </button>
                    </div>
                </div>
                <div
                    class="border border-[#e5e7eb] rounded-[8px] overflow-hidden max-h-[360px] overflow-y-auto bg-[#f9fafb]">
                    <template v-for="group in dbGroupConfig" :key="group.type">
                        <template
                            v-if="(currentDbType === 'all' || currentDbType === group.type) && group.list.length > 0">
                            <div
                                class="flex items-center gap-2 px-[14px] py-[5px] text-[12px] font-bold sticky top-0 z-[5] border-b border-[#e5e7eb]"
                                :class="group.headerClass">
                                <span
                                    class="w-[6px] h-[6px] rounded-full shrink-0"
                                    :class="group.dotClass"
                                    style="background: currentColor"></span>
                                {{ group.label }}
                                <span class="ml-auto text-[11px] opacity-65">{{ group.list.length }}</span>
                            </div>
                            <div
                                v-for="item in group.list"
                                :key="item._key"
                                class="flex items-center gap-2 px-[14px] py-2 border-b border-[#f0f0f0] last:border-b-0 bg-white hover:bg-[#fafbff] transition-colors text-[12.5px] font-mono"
                                :class="dbRowClass(item._key)">
                                <span
                                    class="px-[7px] py-[2px] rounded-[4px] text-[10px] font-bold uppercase tracking-[0.04em] shrink-0"
                                    :class="group.badgeClass"
                                    >{{ group.tagLabel }}</span
                                >
                                <span
                                    class="flex-1 overflow-hidden text-ellipsis whitespace-nowrap min-w-0 text-[#374151]"
                                    :title="item.msg"
                                    >{{ item.msg }}</span
                                >
                                <div class="flex items-center gap-1 shrink-0 ml-auto">
                                    <span
                                        v-if="db.itemStatus[item._key] === 'success'"
                                        class="text-[11px] font-bold text-[#16a34a] whitespace-nowrap"
                                        >✓ 已升级</span
                                    >
                                    <template v-else-if="db.itemStatus[item._key] === 'error'">
                                        <button
                                            class="mini-btn bg-[#dc2626] text-white border-[#dc2626] hover:bg-[#b91c1c]"
                                            :disabled="oneKey.running || db.executing || !!db.itemExecuting[item._key]"
                                            @click.stop="onSingleDbExecute(item)">
                                            <span v-if="!!db.itemExecuting[item._key]" class="spin-ring xs"></span
                                            ><span v-else>↺</span> 重试
                                        </button>
                                        <button
                                            class="mini-btn bg-[transparent] text-[#9ca3af] border-[#e5e7eb] hover:bg-[#fee2e2] hover:text-[#dc2626] hover:border-[#fca5a5]"
                                            :disabled="oneKey.running || db.executing"
                                            @click.stop="onRemoveDbDiff(item)">
                                            ✕
                                        </button>
                                    </template>
                                    <template v-else>
                                        <button
                                            class="mini-btn bg-[#d97706] text-white border-[#d97706] hover:bg-[#b45309]"
                                            :disabled="oneKey.running || db.executing || !!db.itemExecuting[item._key]"
                                            @click.stop="onSingleDbExecute(item)">
                                            <span v-if="!!db.itemExecuting[item._key]" class="spin-ring xs"></span
                                            ><span v-else>▶</span> 执行
                                        </button>
                                        <button
                                            class="mini-btn bg-[transparent] text-[#9ca3af] border-[#e5e7eb] hover:bg-[#fee2e2] hover:text-[#dc2626] hover:border-[#fca5a5]"
                                            :disabled="oneKey.running || db.executing"
                                            @click.stop="onRemoveDbDiff(item)">
                                            ✕
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </template>
                        <div
                            v-if="currentDbType === group.type && group.list.length === 0"
                            class="p-7 text-center text-[#9ca3af] text-[13px]">
                            暂无{{ group.tagLabel }}
                        </div>
                    </template>
                </div>
            </div>

            <!-- SQL 版本文件列表 -->
            <div v-if="sql.tasks.length > 0" class="flex flex-col gap-[10px]">
                <div class="flex flex-wrap items-center justify-between gap-[10px]">
                    <div class="flex items-center gap-2 text-[14px] font-bold text-[#111827]">
                        <span class="w-[3px] h-[16px] rounded-full bg-[#7c3aed] shrink-0"></span>
                        发现 <em class="not-italic text-[#3b82f6]">{{ sql.tasks.length }}</em> 个待处理 SQL 文件
                        <span class="text-[12px] font-normal text-[#6b7280]">当前：{{ sql.currentName }}</span>
                    </div>
                    <div class="flex gap-1 flex-wrap">
                        <button
                            v-for="tab in sqlTabs"
                            :key="tab.key"
                            class="inline-flex items-center gap-[5px] px-3 py-[5px] rounded-[6px] text-[12px] font-medium border cursor-pointer transition-all"
                            :class="
                                currentSqlType === tab.key
                                    ? 'bg-[#3b82f6] border-[#3b82f6] text-white'
                                    : 'bg-[transparent] border-[#e5e7eb] text-[#6b7280] hover:bg-[#f8fafc] hover:text-[#111827]'
                            "
                            @click="currentSqlType = tab.key">
                            {{ tab.label }}
                            <span
                                class="min-w-[17px] text-center px-1 rounded-full text-[10px] font-bold"
                                :class="currentSqlType === tab.key ? 'bg-white/25' : tab.countClass"
                                >{{ tab.count }}</span
                            >
                        </button>
                    </div>
                </div>
                <div
                    class="border border-[#e5e7eb] rounded-[8px] overflow-hidden max-h-[360px] overflow-y-auto bg-[#f9fafb]">
                    <template v-for="group in sqlGroupConfig" :key="group.type">
                        <template
                            v-if="(currentSqlType === 'all' || currentSqlType === group.type) && group.list.length > 0">
                            <div
                                class="flex items-center gap-2 px-[14px] py-[5px] text-[12px] font-bold sticky top-0 z-[5] border-b border-[#e5e7eb]"
                                :class="group.headerClass">
                                <span
                                    class="w-[6px] h-[6px] rounded-full shrink-0"
                                    :class="group.dotClass"
                                    style="background: currentColor"></span>
                                {{ group.label }}
                                <span class="ml-auto text-[11px] opacity-65">{{ group.list.length }}</span>
                            </div>
                            <div
                                v-for="item in group.list"
                                :key="item.file"
                                class="flex items-center gap-2 px-[14px] py-2 border-b border-[#f0f0f0] last:border-b-0 bg-white hover:bg-[#fafbff] transition-colors text-[12.5px] font-mono"
                                :class="sqlRowClass(item)">
                                <span
                                    class="px-[7px] py-[2px] rounded-[4px] text-[10px] font-bold uppercase tracking-[0.04em] shrink-0 bg-[#f5f3ff] text-[#5b21b6]">
                                    {{ item.kind === "version" ? item.version : "建表" }}
                                </span>
                                <span
                                    class="flex-1 overflow-hidden text-ellipsis whitespace-nowrap min-w-0 text-[#374151]"
                                    :title="item.file"
                                    >{{ item.file }}</span
                                >
                                <span
                                    v-if="item.kind === 'version' && item.timestamp"
                                    class="text-[11px] text-[#7c3aed] bg-[#f5f3ff] px-[6px] py-[1px] rounded-[4px] shrink-0"
                                    >{{ formatTimestamp(item.timestamp) }}</span
                                >
                                <span
                                    v-if="item.kind === 'table'"
                                    class="text-[11px] shrink-0 px-[6px] py-[1px] rounded-[4px]"
                                    :class="item.skip ? 'text-[#9ca3af] bg-[#f3f4f6]' : 'text-[#d97706] bg-[#fffbeb]'"
                                    >{{ item.skip ? "表已存在" : "表不存在" }}</span
                                >
                                <span
                                    v-if="item.size"
                                    class="text-[#9ca3af] text-[11px] shrink-0 bg-[#f3f4f6] px-[6px] py-[1px] rounded-[4px]"
                                    >{{ formatSize(item.size) }}</span
                                >
                                <div class="flex items-center gap-1 shrink-0 ml-auto">
                                    <span
                                        v-if="item.skip"
                                        class="text-[11px] font-semibold text-[#6b7280]"
                                        >⊘ 跳过</span
                                    >
                                    <span
                                        v-else-if="sql.taskStatus[item.file] === 'success'"
                                        class="text-[11px] font-bold text-[#16a34a] whitespace-nowrap"
                                        >✓ 已执行</span
                                    >
                                    <template v-else-if="sql.taskStatus[item.file] === 'error'">
                                        <button
                                            class="mini-btn bg-[#dc2626] text-white border-[#dc2626] hover:bg-[#b91c1c]"
                                            :disabled="
                                                oneKey.running || sql.batchRunning || !!sql.taskRunning[item.file]
                                            "
                                            @click.stop="onSqlSingleExecute(item)">
                                            <span v-if="!!sql.taskRunning[item.file]" class="spin-ring xs"></span
                                            ><span v-else>↺</span> 重试
                                        </button>
                                        <button
                                            class="mini-btn bg-[transparent] text-[#9ca3af] border-[#e5e7eb] hover:bg-[#fee2e2] hover:text-[#dc2626] hover:border-[#fca5a5]"
                                            :disabled="oneKey.running || sql.batchRunning"
                                            @click.stop="onRemoveSqlTask(item)">
                                            ✕
                                        </button>
                                    </template>
                                    <template v-else>
                                        <button
                                            class="mini-btn bg-[#7c3aed] text-white border-[#7c3aed] hover:bg-[#6d28d9]"
                                            :disabled="
                                                oneKey.running || sql.batchRunning || !!sql.taskRunning[item.file]
                                            "
                                            @click.stop="onSqlSingleExecute(item)">
                                            <span v-if="!!sql.taskRunning[item.file]" class="spin-ring xs"></span
                                            ><span v-else>▶</span> 执行
                                        </button>
                                        <button
                                            class="mini-btn bg-[transparent] text-[#9ca3af] border-[#e5e7eb] hover:bg-[#fee2e2] hover:text-[#dc2626] hover:border-[#fca5a5]"
                                            :disabled="oneKey.running || sql.batchRunning"
                                            @click.stop="onRemoveSqlTask(item)">
                                            ✕
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </template>
                        <div
                            v-if="currentSqlType === group.type && group.list.length === 0"
                            class="p-7 text-center text-[#9ca3af] text-[13px]">
                            暂无{{ group.label }}
                        </div>
                    </template>
                </div>
            </div>

            <!-- 运行日志 -->
            <div class="flex flex-col gap-2">
                <div class="flex items-center gap-2 text-[14px] font-bold text-[#111827]">
                    <span class="w-[3px] h-[16px] rounded-full bg-[#16a34a] shrink-0"></span>
                    运行日志
                    <span class="ml-auto text-[11px] text-[#6b7280] font-normal">{{ logs.length }} 条</span>
                </div>
                <div
                    ref="logRef"
                    class="bg-[#0f172a] rounded-[8px] px-[15px] py-[13px] h-[220px] overflow-y-auto font-mono text-[12.5px] leading-[1.7] flex flex-col gap-[1px] log-scrollbar">
                    <div v-if="logs.length === 0" class="text-[#38bdf8]">
                        <span class="animate-[blink_0.8s_step-end_infinite]">▌</span> 系统就绪，等待执行检测…
                    </div>
                    <div v-for="(log, i) in logs" :key="i" class="flex gap-[10px]">
                        <span class="text-[#475569] shrink-0 text-[11px] pt-[1px]">{{ log.time }}</span>
                        <span
                            class="flex-1 break-all"
                            :class="
                                log.type === 'success'
                                    ? 'text-[#4ade80]'
                                    : log.type === 'warning'
                                    ? 'text-[#fbbf24]'
                                    : log.type === 'error'
                                    ? 'text-[#f87171] font-bold'
                                    : 'text-[#94a3b8]'
                            ">
                            {{ log.msg }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 数据库升级确认弹窗 -->
        <el-dialog v-model="db.confirmVisible" width="400px" title="确认执行数据库升级" center class="confirm-dialog">
            <div class="flex flex-col items-center gap-[10px] text-center py-2">
                <div class="text-[38px]">🗄️</div>
                <div class="text-[15px] text-[#111827]">
                    即将执行
                    <strong class="text-[22px] text-[#dc2626] font-extrabold">{{ pendingDiffs.length }}</strong> 条 SQL
                    升级语句
                </div>
                <div class="text-[13px] text-[#6b7280]">仅包含新增操作，不会删除任何数据</div>
                <div class="text-[13px] font-bold text-[#dc2626] bg-[#fef2f2] px-5 py-2 rounded-[6px] w-full">
                    ⚠️ 请确保已备份数据库！
                </div>
            </div>
            <template #footer>
                <div class="flex justify-center gap-3">
                    <button
                        class="px-7 py-[9px] rounded-[8px] text-[14px] font-semibold border bg-[#f9fafb] text-[#6b7280] border-[#e5e7eb] hover:bg-[#f3f4f6] cursor-pointer transition-all"
                        @click="db.confirmVisible = false">
                        取消
                    </button>
                    <button
                        class="px-7 py-[9px] rounded-[8px] text-[14px] font-semibold border bg-[#3b82f6] text-white border-[#3b82f6] hover:bg-[#2563eb] cursor-pointer transition-all"
                        @click="onDbExecute">
                        确认执行
                    </button>
                </div>
            </template>
        </el-dialog>

        <!-- 一键更新确认弹窗 -->
        <el-dialog v-model="oneKey.confirmVisible" width="440px" title="确认一键更新" center class="confirm-dialog">
            <div class="flex flex-col items-center gap-[10px] text-center py-2">
                <div class="text-[38px]">🚀</div>
                <div class="text-[15px] text-[#111827]">即将依次执行以下操作：</div>
                <div class="w-full text-left flex flex-col gap-2 py-[2px]">
                    <!-- 数据库 -->
                    <div v-if="pendingDiffs.length > 0" class="flex items-center gap-[10px] text-[13px] text-[#111827]">
                        <span
                            class="w-[22px] h-[22px] rounded-full flex items-center justify-center text-[11px] font-bold shrink-0 bg-[#fef9c3] text-[#d97706]">
                            {{ confirmStepIndex.db }}
                        </span>
                        执行 <b>{{ pendingDiffs.length }}</b> 条数据库升级 SQL
                    </div>

                    <!-- 覆盖目录 -->
                    <div
                        v-if="overwrite.dirs.some((d) => d.has_update)"
                        class="flex items-center gap-[10px] text-[13px] text-[#111827]">
                        <span
                            class="w-[22px] h-[22px] rounded-full flex items-center justify-center text-[11px] font-bold shrink-0 bg-[#fff7ed] text-[#ea580c]">
                            {{ confirmStepIndex.overwrite }}
                        </span>
                        覆盖
                        <b>{{
                            overwrite.dirs
                                .filter((d) => d.has_update)
                                .map((d) => d.dir)
                                .join("、")
                        }}</b>
                        等目录（共
                        {{ overwrite.dirs.filter((d) => d.has_update).reduce((s, d) => s + d.changed, 0) }} 个变更文件）
                    </div>

                    <!-- 普通文件 -->
                    <div v-if="file.tasks.length > 0" class="flex items-center gap-[10px] text-[13px] text-[#111827]">
                        <span
                            class="w-[22px] h-[22px] rounded-full flex items-center justify-center text-[11px] font-bold shrink-0 bg-[#dbeafe] text-[#3b82f6]">
                            {{ confirmStepIndex.file }}
                        </span>
                        同步 <b>{{ file.tasks.length }}</b> 个差异文件
                    </div>

                    <!-- SQL 版本 -->
                    <div
                        v-if="pendingSqlTasks.length > 0"
                        class="flex items-center gap-[10px] text-[13px] text-[#111827]">
                        <span
                            class="w-[22px] h-[22px] rounded-full flex items-center justify-center text-[11px] font-bold shrink-0 bg-[#f5f3ff] text-[#7c3aed]">
                            {{ confirmStepIndex.sql }}
                        </span>
                        执行 <b>{{ pendingSqlTasks.length }}</b> 个 SQL 版本升级文件
                    </div>
                </div>
                <div class="text-[13px] font-bold text-[#dc2626] bg-[#fef2f2] px-5 py-2 rounded-[6px] w-full">
                    ⚠️ 请确保已备份系统和数据库！
                </div>
            </div>
            <template #footer>
                <div class="flex justify-center gap-3">
                    <button
                        class="px-7 py-[9px] rounded-[8px] text-[14px] font-semibold border bg-[#f9fafb] text-[#6b7280] border-[#e5e7eb] hover:bg-[#f3f4f6] cursor-pointer transition-all"
                        @click="oneKey.confirmVisible = false">
                        取消
                    </button>
                    <button
                        class="px-7 py-[9px] rounded-[8px] text-[14px] font-semibold border bg-[#dc2626] text-white border-[#dc2626] hover:bg-[#b91c1c] cursor-pointer transition-all"
                        @click="onOneKeyUpdate">
                        确认开始更新
                    </button>
                </div>
            </template>
        </el-dialog>

        <el-drawer v-model="diffState.visible" size="90%" direction="btt" destroy-on-close class="diff-drawer">
            <template #header>
                <div class="flex items-center gap-[10px] flex-wrap w-full min-w-0">
                    <span
                        class="font-mono text-[13px] font-semibold text-[#3b82f6] overflow-hidden text-ellipsis whitespace-nowrap max-w-[55vw]"
                        :title="diffState.file"
                        >{{ diffState.file }}</span
                    >
                    <span class="px-2 py-[2px] rounded-[4px] text-[11px] font-bold bg-[#fef9c3] text-[#92400e] shrink-0"
                        >内容变更</span
                    >
                    <div class="ml-auto flex gap-4 text-[12px] text-[#6b7280] shrink-0">
                        <span class="flex items-center gap-[5px]"
                            ><span class="w-3 h-3 rounded-[3px] bg-[#ffd7d5] border border-[#fca5a5]"></span
                            >本地旧版</span
                        >
                        <span class="flex items-center gap-[5px]"
                            ><span class="w-3 h-3 rounded-[3px] bg-[#ccffd8] border border-[#86efac]"></span
                            >远端新版</span
                        >
                    </div>
                </div>
            </template>
            <div v-if="diffState.loading" class="p-6"><el-skeleton :rows="16" animated /></div>
            <el-empty v-else-if="!diffState.loading && diffState.hunks.length === 0" description="暂无差异数据" />
            <div v-else class="flex-1 overflow-auto p-4 font-mono text-[12.5px] leading-[1.65]">
                <div
                    v-for="(hunk, hi) in diffState.hunks"
                    :key="hi"
                    class="mb-4 border border-[#e5e7eb] rounded-[7px] overflow-hidden">
                    <div
                        class="bg-[#f0f4ff] text-[#6b7fd7] px-[14px] py-[3px] text-[11px] border-b border-[#dce3f5] select-none">
                        @@ 本地第 {{ hunk.local_start }} 行 / 远端第 {{ hunk.remote_start }} 行 @@
                    </div>
                    <div class="flex">
                        <div class="flex-1 min-w-0 overflow-x-auto">
                            <template v-for="(ln, li) in hunk.lines" :key="li">
                                <div
                                    v-if="ln.type !== 'insert'"
                                    class="flex items-baseline min-h-[21px] pr-2 whitespace-pre"
                                    :class="ln.type === 'delete' ? 'bg-[#ffd7d5]' : 'bg-white'">
                                    <span
                                        class="w-11 min-w-[44px] text-right pr-[10px] text-[#bbb] shrink-0 select-none text-[11px]"
                                        >{{ ln.local_no ?? "" }}</span
                                    >
                                    <span
                                        class="w-[18px] min-w-[18px] text-center shrink-0 select-none font-bold"
                                        :class="ln.type === 'delete' ? 'text-[#cf222e]' : ''"
                                        >{{ ln.type === "delete" ? "－" : "\u00a0" }}</span
                                    >
                                    <span class="flex-1 tab-size-4">{{ ln.text }}</span>
                                </div>
                                <div v-else class="bg-[#fafafa] min-h-[21px]">&nbsp;</div>
                            </template>
                        </div>
                        <div class="w-[2px] bg-[#e5e7eb] shrink-0"></div>
                        <div class="flex-1 min-w-0 overflow-x-auto">
                            <template v-for="(ln, li) in hunk.lines" :key="li">
                                <div
                                    v-if="ln.type !== 'delete'"
                                    class="flex items-baseline min-h-[21px] pr-2 whitespace-pre"
                                    :class="ln.type === 'insert' ? 'bg-[#ccffd8]' : 'bg-white'">
                                    <span
                                        class="w-11 min-w-[44px] text-right pr-[10px] text-[#bbb] shrink-0 select-none text-[11px]"
                                        >{{ ln.remote_no ?? "" }}</span
                                    >
                                    <span
                                        class="w-[18px] min-w-[18px] text-center shrink-0 select-none font-bold"
                                        :class="ln.type === 'insert' ? 'text-[#1a7f37]' : ''"
                                        >{{ ln.type === "insert" ? "＋" : "\u00a0" }}</span
                                    >
                                    <span class="flex-1 tab-size-4">{{ ln.text }}</span>
                                </div>
                                <div v-else class="bg-[#fafafa] min-h-[21px]">&nbsp;</div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </el-drawer>
    </div>
</template>

<script setup lang="ts">
import {
    fullCompare,
    overwriteDir,
    fileSync,
    fileDiff,
    dbCompare,
    dbExecute,
    sqlCompare,
    sqlExecute,
    versionUpdate,
    checkVersion,
    getNotice,
} from "@/api/setting/update";

import useAppStore from "@/stores/modules/app";
import feedback from "@/utils/feedback";

// ==================== 类型定义 ====================
interface DbDiffItem {
    type: "new_table" | "add_column" | "modify_column" | "add_index";
    msg: string;
    _key: string;
    _originIndex: number;
}
interface SqlTask {
    file: string;
    kind: "version" | "table";
    version?: string;
    number?: number;
    timestamp?: number;
    table?: string;
    size: number;
    skip: boolean;
    reason?: string;
}
interface DiffLine {
    type: "context" | "delete" | "insert";
    local_no: number | null;
    remote_no: number | null;
    text: string;
}
interface DiffHunk {
    local_start: number;
    remote_start: number;
    lines: DiffLine[];
}
interface NoticeItem {
    version: string;
    date: string;
    title: string;
    type: "feature" | "bugfix" | "security";
    items: string[];
}
// ==================== 覆盖目录 ====================
interface OverwriteDir {
    dir: string;
    changed: number;
    total: number;
    size: number;
    has_update: boolean;
    files: { file: string; type: "new" | "modify"; size: number }[];
}

// ==================== 基础状态 ====================
const appStore = useAppStore();
const getSystemVersion = computed(() => appStore.config?.version?.version_name);
const logRef = ref<HTMLElement>();
const logs = ref<{ time: string; msg: string; type: string }[]>([]);
const detecting = ref(false);
const detected = ref(false);
const detectStep = ref<"file" | "db" | "sql" | "">("");
const detectSteps = [
    { key: "file", label: "检测文件差异" },
    { key: "db", label: "检测数据库差异" },
    { key: "sql", label: "检测 SQL 版本" },
];
const detectStepIndex = computed(() => detectSteps.findIndex((s) => s.key === detectStep.value));
const currentFileType = ref("all");
const currentDbType = ref("all");
const currentSqlType = ref("all");

// ==================== 版本信息 ====================
const versionInfo = reactive({
    loading: false,
    saving: false,
    localNumber: 0,
    localName: "",
    remoteNumber: 0,
    remoteName: "",
    upToDate: false,
    saved: false,
});

const overwrite = reactive({
    syncing: false,
    syncDone: false,
    dirs: [] as OverwriteDir[], // 来自 fullCompare 的 overwrite 数组
    dirStatus: {} as Record<string, "running" | "success" | "error" | "">,
    progressDone: 0,
    progressTotal: 0,
    progressPercent: 0,
    progressLabel: "",
    progressStatus: "" as "" | "success" | "exception",
});

// ==================== 更新公告 ====================
const notice = reactive({
    loading: false,
    error: false,
    latestVersion: "",
    list: [] as NoticeItem[],
    expanded: null as string | null,
});

async function loadNotice() {
    notice.loading = true;
    notice.error = false;
    try {
        const res = (await getNotice()) as any;
        notice.list = res.notices ?? [];
        notice.latestVersion = res.latest_version ?? "";
        if (notice.list.length > 0) notice.expanded = notice.list[0].version;
    } catch {
        notice.error = true;
    } finally {
        notice.loading = false;
    }
}

// ==================== 一键更新 ====================
const oneKey = reactive({
    running: false,
    confirmVisible: false,
    currentStep: "" as string,
    stepStatus: {} as Record<string, "success" | "error" | "">,
    totalErrors: 0,
});
const oneKeySteps = computed(() => {
    const steps = [];
    if (db.diffs.length > 0 || db.execDone) steps.push({ key: "db", label: "升级数据库" });
    if (file.tasks.length > 0 || file.syncDone || overwrite.dirs.some((d) => d.has_update)) {
        steps.push({ key: "file", label: "同步文件" });
    }
    if (sql.tasks.filter((t) => !t.skip).length > 0 || sql.batchDone) steps.push({ key: "sql", label: "执行 SQL" });
    steps.push({ key: "version", label: "写入版本号" });
    return steps;
});
function oneKeyStepDone(idx: number) {
    const key = oneKeySteps.value[idx]?.key;
    return key && oneKey.stepStatus[key] !== undefined && oneKey.stepStatus[key] !== "";
}

// ==================== 文件 ====================
const file = reactive({
    syncing: false,
    syncDone: false,
    tasks: [] as any[],
    progressDone: 0,
    progressTotal: 0,
    progressPercent: 0,
    progressLabel: "",
    progressStatus: "" as "" | "success" | "exception",
    taskStatus: {} as Record<string, string>,
    taskSyncing: {} as Record<string, boolean>,
    silentDone: false,
});

// ==================== 数据库 ====================
const db = reactive({
    executing: false,
    execDone: false,
    confirmVisible: false,
    diffs: [] as DbDiffItem[],
    cacheKey: "",
    progressDone: 0,
    progressPercent: 0,
    progressLabel: "",
    progressStatus: "" as "" | "success" | "exception",
    executingTotal: 0,
    itemStatus: {} as Record<string, string>,
    itemExecuting: {} as Record<string, boolean>,
});

// ==================== SQL ====================
const sql = reactive({
    tasks: [] as SqlTask[],
    currentName: "",
    batchRunning: false,
    batchDone: false,
    progressDone: 0,
    progressTotal: 0,
    progressPercent: 0,
    progressLabel: "",
    progressStatus: "" as "" | "success" | "exception",
    taskStatus: {} as Record<string, string>,
    taskRunning: {} as Record<string, boolean>,
});

// ==================== diff 抽屉 ====================
const diffState = reactive({ visible: false, loading: false, loadingFile: "", file: "", hunks: [] as DiffHunk[] });

// ==================== 计算属性 ====================
const pendingDiffs = computed(() => db.diffs.filter((d) => db.itemStatus[d._key] !== "success"));
const pendingSqlTasks = computed(() => sql.tasks.filter((t) => !t.skip && sql.taskStatus[t.file] !== "success"));
const hasAnyPending = computed(
    () =>
        file.tasks.length > 0 ||
        overwrite.dirs.some((d) => d.has_update) ||
        pendingDiffs.value.length > 0 ||
        pendingSqlTasks.value.length > 0,
);

const hasAnyStepError = computed(
    () =>
        file.tasks.some((t: any) => file.taskStatus[t.file] === "error") ||
        db.diffs.some((d) => db.itemStatus[d._key] === "error") ||
        sql.tasks.some((t) => !t.skip && sql.taskStatus[t.file] === "error"),
);
const showVersionSaveBtn = computed(() => {
    if (!detected.value) return false;
    if (oneKey.running || file.syncing || db.executing || sql.batchRunning) return false;
    if (versionInfo.saved) return false;
    return file.syncDone || overwrite.syncDone || db.execDone || sql.batchDone;
});

// ==================== 分组 ====================
const groupedFileTasks = computed(() => ({
    newFiles: file.tasks.filter((t: any) => t.type === "new"),
    modifyFiles: file.tasks.filter((t: any) => t.type === "modify"),
    otherFiles: file.tasks.filter((t: any) => t.type !== "new" && t.type !== "modify"),
    errorFiles: file.tasks.filter((t: any) => file.taskStatus[t.file] === "error"),
}));
const groupedDbDiffs = computed(() => ({
    newTable: db.diffs.filter((d) => d.type === "new_table"),
    addColumn: db.diffs.filter((d) => d.type === "add_column"),
    modifyColumn: db.diffs.filter((d) => d.type === "modify_column"),
    addIndex: db.diffs.filter((d) => d.type === "add_index"),
    errorItems: db.diffs.filter((d) => db.itemStatus[d._key] === "error"),
}));
const groupedSqlTasks = computed(() => ({
    versionTasks: sql.tasks.filter((t) => t.kind === "version"),
    tableTasks: sql.tasks.filter((t) => t.kind === "table"),
    errorTasks: sql.tasks.filter((t) => sql.taskStatus[t.file] === "error"),
}));

// ==================== Tabs ====================
const fileTabs = computed(() => [
    { key: "all", label: "全部", count: file.tasks.length, countClass: "" },
    {
        key: "new",
        label: "新增",
        count: groupedFileTasks.value.newFiles.length,
        countClass: "bg-[#dcfce7] text-[#16a34a]",
    },
    {
        key: "modify",
        label: "修改",
        count: groupedFileTasks.value.modifyFiles.length,
        countClass: "bg-[#fef9c3] text-[#d97706]",
    },
    { key: "other", label: "其他", count: groupedFileTasks.value.otherFiles.length, countClass: "" },
    ...(groupedFileTasks.value.errorFiles.length > 0
        ? [
              {
                  key: "error",
                  label: "失败",
                  count: groupedFileTasks.value.errorFiles.length,
                  countClass: "bg-[#fee2e2] text-[#dc2626]",
              },
          ]
        : []),
]);
const dbTabs = computed(() => [
    { key: "all", label: "全部", count: db.diffs.length, countClass: "" },
    {
        key: "new_table",
        label: "新增表",
        count: groupedDbDiffs.value.newTable.length,
        countClass: "bg-[#fee2e2] text-[#dc2626]",
    },
    {
        key: "add_column",
        label: "新增字段",
        count: groupedDbDiffs.value.addColumn.length,
        countClass: "bg-[#dcfce7] text-[#16a34a]",
    },
    {
        key: "modify_column",
        label: "修改字段",
        count: groupedDbDiffs.value.modifyColumn.length,
        countClass: "bg-[#fef9c3] text-[#d97706]",
    },
    {
        key: "add_index",
        label: "新增索引",
        count: groupedDbDiffs.value.addIndex.length,
        countClass: "bg-[#dbeafe] text-[#3b82f6]",
    },
    ...(groupedDbDiffs.value.errorItems.length > 0
        ? [
              {
                  key: "error",
                  label: "失败",
                  count: groupedDbDiffs.value.errorItems.length,
                  countClass: "bg-[#fee2e2] text-[#dc2626]",
              },
          ]
        : []),
]);
const sqlTabs = computed(() => [
    { key: "all", label: "全部", count: sql.tasks.length, countClass: "" },
    {
        key: "version",
        label: "版本升级",
        count: groupedSqlTasks.value.versionTasks.length,
        countClass: "bg-[#f5f3ff] text-[#7c3aed]",
    },
    {
        key: "table",
        label: "建表文件",
        count: groupedSqlTasks.value.tableTasks.length,
        countClass: "bg-[#dbeafe] text-[#3b82f6]",
    },
    ...(groupedSqlTasks.value.errorTasks.length > 0
        ? [
              {
                  key: "error",
                  label: "失败",
                  count: groupedSqlTasks.value.errorTasks.length,
                  countClass: "bg-[#fee2e2] text-[#dc2626]",
              },
          ]
        : []),
]);

// ==================== 分组配置 ====================
const fileGroupConfig = computed(() => [
    {
        type: "new",
        label: "新增文件",
        tagLabel: "新增",
        badgeClass: "bg-[#dcfce7] text-[#166534]",
        dotClass: "text-[#16a34a]",
        headerClass: "bg-[#f0fdf4] text-[#16a34a]",
        list: groupedFileTasks.value.newFiles,
    },
    {
        type: "modify",
        label: "修改文件",
        tagLabel: "修改",
        badgeClass: "bg-[#fef9c3] text-[#92400e]",
        dotClass: "text-[#d97706]",
        headerClass: "bg-[#fffbeb] text-[#d97706]",
        list: groupedFileTasks.value.modifyFiles,
    },
    {
        type: "other",
        label: "其他文件",
        tagLabel: "其他",
        badgeClass: "bg-[#f3f4f6] text-[#4b5563]",
        dotClass: "text-[#6b7280]",
        headerClass: "bg-[#f3f4f6] text-[#6b7280]",
        list: groupedFileTasks.value.otherFiles,
    },
    {
        type: "error",
        label: "同步失败",
        tagLabel: "失败",
        badgeClass: "bg-[#fee2e2] text-[#991b1b]",
        dotClass: "text-[#dc2626]",
        headerClass: "bg-[#fef2f2] text-[#dc2626]",
        list: groupedFileTasks.value.errorFiles,
    },
]);
const dbGroupConfig = computed(() => [
    {
        type: "new_table",
        label: "新增表",
        tagLabel: "新增表",
        badgeClass: "bg-[#fee2e2] text-[#991b1b]",
        dotClass: "text-[#dc2626]",
        headerClass: "bg-[#fef2f2] text-[#dc2626]",
        list: groupedDbDiffs.value.newTable,
    },
    {
        type: "add_column",
        label: "新增字段",
        tagLabel: "新增字段",
        badgeClass: "bg-[#dcfce7] text-[#166534]",
        dotClass: "text-[#16a34a]",
        headerClass: "bg-[#f0fdf4] text-[#16a34a]",
        list: groupedDbDiffs.value.addColumn,
    },
    {
        type: "modify_column",
        label: "修改字段",
        tagLabel: "修改字段",
        badgeClass: "bg-[#fef9c3] text-[#92400e]",
        dotClass: "text-[#d97706]",
        headerClass: "bg-[#fffbeb] text-[#d97706]",
        list: groupedDbDiffs.value.modifyColumn,
    },
    {
        type: "add_index",
        label: "新增索引",
        tagLabel: "新增索引",
        badgeClass: "bg-[#dbeafe] text-[#1e40af]",
        dotClass: "text-[#3b82f6]",
        headerClass: "bg-[#eff6ff] text-[#3b82f6]",
        list: groupedDbDiffs.value.addIndex,
    },
    {
        type: "error",
        label: "执行失败",
        tagLabel: "失败",
        badgeClass: "bg-[#fee2e2] text-[#991b1b]",
        dotClass: "text-[#dc2626]",
        headerClass: "bg-[#fef2f2] text-[#dc2626]",
        list: groupedDbDiffs.value.errorItems,
    },
]);
const sqlGroupConfig = computed(() => [
    {
        type: "version",
        label: "版本升级文件",
        dotClass: "text-[#7c3aed]",
        headerClass: "bg-[#f5f3ff] text-[#7c3aed]",
        list: groupedSqlTasks.value.versionTasks,
    },
    {
        type: "table",
        label: "建表文件",
        dotClass: "text-[#3b82f6]",
        headerClass: "bg-[#eff6ff] text-[#3b82f6]",
        list: groupedSqlTasks.value.tableTasks,
    },
    {
        type: "error",
        label: "执行失败",
        dotClass: "text-[#dc2626]",
        headerClass: "bg-[#fef2f2] text-[#dc2626]",
        list: groupedSqlTasks.value.errorTasks,
    },
]);

const confirmStepIndex = computed(() => {
    let i = 0;
    return {
        db: pendingDiffs.value.length > 0 ? ++i : 0,
        overwrite: overwrite.dirs.some((d) => d.has_update) ? ++i : 0,
        file: file.tasks.length > 0 ? ++i : 0,
        sql: pendingSqlTasks.value.length > 0 ? ++i : 0,
    };
});

// ==================== 行样式 ====================
function fileRowClass(f: string) {
    const s = file.taskStatus[f];
    if (s === "success") return "!bg-[#f0fdf4]";
    if (s === "error") return "!bg-[#fff5f5]";
    return "";
}
function dbRowClass(k: string) {
    const s = db.itemStatus[k];
    if (s === "success") return "!bg-[#f0fdf4]";
    if (s === "error") return "!bg-[#fff5f5]";
    return "";
}
function sqlRowClass(item: SqlTask) {
    if (item.skip) return "!bg-[#f9fafb] opacity-65";
    const s = sql.taskStatus[item.file];
    if (s === "success") return "!bg-[#f0fdf4]";
    if (s === "error") return "!bg-[#fff5f5]";
    return "";
}

// ==================== 工具函数 ====================
function now() {
    return new Date().toLocaleTimeString();
}
function addLog(msg: string, type = "info") {
    logs.value.push({ time: now(), msg, type });
    nextTick(() => {
        if (logRef.value) logRef.value.scrollTop = logRef.value.scrollHeight;
    });
}
function formatSize(b: number) {
    if (!b) return "";
    if (b < 1024) return b + " B";
    if (b < 1048576) return (b / 1024).toFixed(1) + " KB";
    return (b / 1048576).toFixed(2) + " MB";
}
function formatTimestamp(ts: number): string {
    if (!ts) return "";
    const s = String(ts);
    if (s.length < 12) return s;
    return `${s.slice(0, 4)}-${s.slice(4, 6)}-${s.slice(6, 8)} ${s.slice(8, 10)}:${s.slice(10, 12)}`;
}

// ==================== 版本信息加载 ====================
async function loadVersionInfo() {
    versionInfo.loading = true;
    try {
        const res = (await checkVersion()) as any;
        versionInfo.localNumber = res.local_version ?? 0;
        versionInfo.localName = res.local_name ?? "";
        versionInfo.remoteNumber = res.remote_version ?? 0;
        versionInfo.remoteName = res.remote_name ?? "";
        versionInfo.upToDate = !res.has_update;
    } catch {
    } finally {
        versionInfo.loading = false;
    }
}

async function onVersionSave() {
    versionInfo.saving = true;
    addLog("📌 写入版本号...");
    try {
        const res = (await versionUpdate()) as any;
        versionInfo.localName = res.version_name ?? versionInfo.remoteName;
        versionInfo.localNumber = res.version_number ?? versionInfo.remoteNumber;
        versionInfo.upToDate = true;
        versionInfo.saved = true;
        addLog(`✅ 版本号已更新为 ${versionInfo.localName}`, "success");
        feedback.msgSuccess(`版本号已更新为 ${versionInfo.localName}`);
    } catch (e: any) {
        addLog(`版本号写入失败：${e.message ?? e}`, "error");
        feedback.msgError("版本号写入失败，请稍后重试");
    } finally {
        versionInfo.saving = false;
    }
}

onMounted(() => {
    loadVersionInfo();
    loadNotice();
});

// ==================== 检测差异 ====================
async function onDetectAll() {
    detecting.value = true;
    detected.value = false;
    detectStep.value = "";
    versionInfo.saved = false;
    file.tasks = [];
    overwrite.dirs = [];
    overwrite.dirStatus = {};
    overwrite.syncing = false;
    overwrite.syncDone = false;
    overwrite.progressDone = 0;
    overwrite.progressTotal = 0;
    overwrite.progressPercent = 0;
    overwrite.progressStatus = "";
    overwrite.progressLabel = "";

    file.taskStatus = {};
    file.taskSyncing = {};
    file.syncDone = false;
    file.progressDone = 0;
    file.progressTotal = 0;
    file.progressPercent = 0;
    file.progressStatus = "";
    file.progressLabel = "";
    file.silentDone = false;
    currentFileType.value = "all";
    db.diffs = [];
    db.cacheKey = "";
    db.execDone = false;
    db.progressDone = 0;
    db.progressPercent = 0;
    db.progressStatus = "";
    db.progressLabel = "";
    db.itemStatus = {};
    db.itemExecuting = {};
    currentDbType.value = "all";
    sql.tasks = [];
    sql.currentName = "";
    sql.batchDone = false;
    sql.progressDone = 0;
    sql.progressTotal = 0;
    sql.progressPercent = 0;
    sql.progressStatus = "";
    sql.progressLabel = "";
    sql.taskStatus = {};
    sql.taskRunning = {};
    currentSqlType.value = "all";

    detectStep.value = "file";
    addLog("📁 [1/3] 开始检测文件差异（含覆盖目录）...");
    try {
        const res = (await fullCompare()) as any;
        // 普通文件差异
        file.tasks = res.diffs ?? [];
        // 覆盖目录变更摘要
        overwrite.dirs = res.overwrite ?? [];
        overwrite.dirStatus = {};

        const owHasUpdate = overwrite.dirs.filter((d) => d.has_update);
        addLog(
            file.tasks.length === 0
                ? "📁 普通文件：与云端完全一致，无差异"
                : `📁 普通文件：发现 ${file.tasks.length} 个差异文件`,
            file.tasks.length === 0 ? "success" : "warning",
        );
        if (owHasUpdate.length > 0) {
            owHasUpdate.forEach((d) => addLog(`🗂️ 覆盖目录 ${d.dir} 有 ${d.changed} 个文件变更`, "warning"));
        } else {
            addLog("🗂️ 覆盖目录：均无变更", "success");
        }
    } catch (e: any) {
        addLog(`📁 文件比对失败：${e.message ?? e}`, "error");
    }

    detectStep.value = "db";
    addLog("🗄️ [2/3] 开始检测数据库结构差异...");
    try {
        const res = (await dbCompare()) as any;
        db.cacheKey = res.cache_key ?? "";
        db.diffs = (res.diffs ?? []).map(
            (d: any, i: number): DbDiffItem => ({ ...d, _key: String(i), _originIndex: i }),
        );
        if (db.diffs.length === 0) {
            addLog("🗄️ 数据库：结构与云端完全一致，无差异", "success");
        } else {
            db.diffs.forEach((d) => addLog(`🗄️ 检测到差异：${d.msg}`, "warning"));
            addLog(`🗄️ 数据库：发现 ${db.diffs.length} 项结构差异`, "warning");
        }
    } catch (e: any) {
        addLog(`🗄️ 数据库比对失败：${e.message ?? e}`, "error");
    }

    detectStep.value = "sql";
    addLog("📜 [3/3] 开始检测 SQL 版本差异...");
    try {
        const res = (await sqlCompare()) as any;
        sql.currentName = res.local_name ?? "";
        sql.tasks = res.tasks ?? [];
        if (res.local_version) versionInfo.localNumber = res.local_version;
        if (res.local_name) versionInfo.localName = res.local_name;
        if (res.remote_version) versionInfo.remoteNumber = res.remote_version;
        if (res.remote_name) versionInfo.remoteName = res.remote_name;
        versionInfo.upToDate = versionInfo.localNumber >= versionInfo.remoteNumber;
        const pending = sql.tasks.filter((t: SqlTask) => !t.skip);
        addLog(
            pending.length === 0
                ? "📜 SQL：已是最新版本，无待执行文件"
                : `📜 SQL：发现 ${pending.length} 个待执行版本文件`,
            pending.length === 0 ? "success" : "warning",
        );
        const skipped = sql.tasks.filter((t: SqlTask) => t.skip);
        if (skipped.length > 0) addLog(`📜 SQL：${skipped.length} 个文件已跳过（已执行过或表已存在）`, "info");
    } catch (e: any) {
        addLog(`📜 SQL 版本检测失败：${e.message ?? e}`, "error");
    }

    detectStep.value = "";
    detecting.value = false;
    detected.value = true;
    addLog("✅ 检测完成", "success");
}

// ==================== SQL 刷新 ====================
async function refreshSqlTasks(): Promise<number> {
    try {
        const res = (await sqlCompare()) as any;
        const freshTasks: SqlTask[] = res.tasks ?? [];
        if (res.local_version) versionInfo.localNumber = res.local_version;
        if (res.local_name) versionInfo.localName = res.local_name;
        if (res.remote_version) versionInfo.remoteNumber = res.remote_version;
        if (res.remote_name) versionInfo.remoteName = res.remote_name;
        versionInfo.upToDate = versionInfo.localNumber >= versionInfo.remoteNumber;
        const existingFiles = new Set(sql.tasks.map((t) => t.file));
        const added: SqlTask[] = [];
        for (const task of freshTasks) {
            if (!existingFiles.has(task.file)) {
                sql.tasks.push(task);
                added.push(task);
            }
        }
        if (added.length > 0)
            addLog(`📜 文件同步带入 ${added.length} 个新 SQL 文件：${added.map((t) => t.file).join("、")}`, "warning");
        else addLog("📜 SQL 文件无新增", "info");
        return added.length;
    } catch (e: any) {
        addLog(`📜 SQL 重新检测失败（已跳过）：${e.message ?? e}`, "error");
        return 0;
    }
}

// ==================== 一键更新 ====================
function onOneKeyUpdateConfirm() {
    oneKey.confirmVisible = true;
}

async function onOneKeyUpdate() {
    oneKey.confirmVisible = false;
    oneKey.running = true;
    oneKey.currentStep = "";
    oneKey.stepStatus = {};
    oneKey.totalErrors = 0;
    versionInfo.saved = false;
    addLog("🚀 ===== 开始一键更新 =====", "warning");

    // 先升级数据库结构，避免「新代码 + 旧库表」导致定时任务报错
    if (pendingDiffs.value.length > 0) {
        oneKey.currentStep = "db";
        addLog("🗄️ 步骤一：数据库升级...");
        const dbErrCount = await runDbExecute();
        oneKey.stepStatus["db"] = dbErrCount === 0 ? "success" : "error";
        oneKey.totalErrors += dbErrCount;
        if (dbErrCount > 0) {
            addLog(`🗄️ 数据库升级有 ${dbErrCount} 个失败，中止后续步骤`, "error");
            oneKey.stepStatus["version"] = "error";
            oneKey.currentStep = "";
            oneKey.running = false;
            addLog(`⚠️ ===== 更新中止，共 ${oneKey.totalErrors} 个失败，请查看日志 =====`, "error");
            feedback.msgWarning("数据库升级失败，更新已中止，请查看日志");
            return;
        }
    }

    oneKey.currentStep = "file";
    addLog("📁 步骤二：文件同步...");
    const fileErrCount = await runFileSync();
    oneKey.stepStatus["file"] = fileErrCount === 0 ? "success" : "error";
    oneKey.totalErrors += fileErrCount;
    if (fileErrCount > 0) addLog(`📁 文件同步有 ${fileErrCount} 个失败，继续执行后续步骤...`, "warning");

    if (pendingSqlTasks.value.length > 0) {
        oneKey.currentStep = "sql";
        addLog("📜 步骤三：SQL 版本升级...");
        const sqlErrCount = await runSqlBatch();
        oneKey.stepStatus["sql"] = sqlErrCount === 0 ? "success" : "error";
        oneKey.totalErrors += sqlErrCount;
        if (sqlErrCount > 0) {
            addLog(`📜 SQL 升级有 ${sqlErrCount} 个失败，中止后续步骤`, "error");
            oneKey.stepStatus["version"] = "error";
            oneKey.currentStep = "";
            oneKey.running = false;
            addLog(`⚠️ ===== 更新中止，共 ${oneKey.totalErrors} 个失败，请查看日志 =====`, "error");
            feedback.msgWarning("SQL 升级失败，更新已中止，请查看日志");
            return;
        }
    }

    oneKey.currentStep = "version";
    addLog("📌 步骤四：写入版本号...");
    try {
        const res = (await versionUpdate()) as any;
        versionInfo.localName = res.version_name ?? versionInfo.remoteName;
        versionInfo.localNumber = res.version_number ?? versionInfo.remoteNumber;
        versionInfo.upToDate = true;
        versionInfo.saved = true;
        oneKey.stepStatus["version"] = "success";
        addLog(`✅ 版本号已更新为 ${versionInfo.localName}`, "success");
    } catch (e: any) {
        oneKey.stepStatus["version"] = "error";
        addLog(`版本号写入失败：${e.message ?? e}`, "error");
    }

    oneKey.currentStep = "";
    oneKey.running = false;
    if (oneKey.totalErrors === 0 && oneKey.stepStatus["version"] === "success") {
        addLog("🎉 ===== 一键更新全部完成！=====", "success");
        feedback.msgSuccess("一键更新完成！");
    } else {
        addLog(`⚠️ ===== 更新结束，请查看日志 =====`, "error");
        feedback.msgWarning("更新完成，请查看日志确认结果");
    }
}

// ==================== 文件同步 ====================
async function runFileSync(): Promise<number> {
    file.syncing = true;
    file.syncDone = false;
    file.progressDone = 0;
    file.progressTotal = 0;
    file.progressPercent = 0;
    file.progressStatus = "";
    let errCount = 0;

    // ---- 阶段一：逐目录覆盖（只覆盖有变更的目录）----
    const needOverwrite = overwrite.dirs.filter((d) => d.has_update && overwrite.dirStatus[d.dir] !== "success");
    if (needOverwrite.length > 0) {
        overwrite.syncing = true;
        overwrite.syncDone = false;
        overwrite.progressDone = 0;
        overwrite.progressTotal = needOverwrite.length;
        overwrite.progressPercent = 0;
        overwrite.progressStatus = "";

        addLog(`⏳ 阶段一：覆盖 ${needOverwrite.length} 个有变更的目录...`);

        for (let i = 0; i < needOverwrite.length; i++) {
            const d = needOverwrite[i];
            overwrite.dirStatus[d.dir] = "running";
            overwrite.progressLabel = `正在覆盖：${d.dir}`;
            addLog(`🗂️ 覆盖目录：${d.dir}（${d.changed} 个变更文件）`);
            try {
                const res = (await overwriteDir(d.dir)) as any;
                const detail = res.details?.[0] ?? { count: 0, errors: [] };
                overwrite.dirStatus[d.dir] = (detail.errors ?? []).length > 0 ? "error" : "success";
                addLog(`✅ ${d.dir} 覆盖完成（共 ${detail.count ?? 0} 个文件）`, "success");
                if ((detail.errors ?? []).length > 0) {
                    detail.errors.forEach((e: any) => addLog(`覆盖失败：${e.file} — ${e.msg}`, "error"));
                    errCount += detail.errors.length;
                }
            } catch (e: any) {
                overwrite.dirStatus[d.dir] = "error";
                addLog(`❌ ${d.dir} 覆盖失败：${e.message ?? e}`, "error");
                errCount++;
            }
            overwrite.progressDone = i + 1;
            overwrite.progressPercent = Math.round(((i + 1) / needOverwrite.length) * 100);
        }

        overwrite.syncing = false;
        overwrite.syncDone = true;
        overwrite.progressStatus = errCount > 0 ? "exception" : "success";
        overwrite.progressLabel = errCount > 0 ? `覆盖完成（${errCount} 个目录失败）` : "全部目录覆盖成功 🎉";
    } else {
        addLog("⏭️ 覆盖目录均无变更，跳过阶段一", "info");
    }

    // ---- 阶段二：同步普通差异文件 ----
    const pendingTasks = file.tasks.filter((t) => file.taskStatus[t.file] !== "success");
    const total = pendingTasks.length;
    if (total > 0) {
        addLog(`⏳ 阶段二：同步 ${total} 个业务差异文件...`);
        file.progressTotal = total;
        for (let i = 0; i < total; i++) {
            const task = pendingTasks[i];
            file.progressLabel = `正在同步：${task.file}`;
            try {
                await fileSync(task.file);
                file.taskStatus[task.file] = "success";
                addLog(`同步成功：${task.file}`, "success");
            } catch (e: any) {
                file.taskStatus[task.file] = "error";
                addLog(`同步失败：${task.file} — ${e.message ?? e}`, "error");
                errCount++;
            }
            file.progressDone = i + 1;
            file.progressPercent = Math.round(((i + 1) / total) * 100);
        }
    } else {
        file.progressPercent = 100;
        addLog("⏭️ 无普通差异文件需要同步", "info");
    }

    file.syncing = false;
    file.syncDone = true;
    if (errCount > 0) {
        file.progressStatus = "exception";
        file.progressLabel = `同步完成（${errCount} 个失败，请查看日志）`;
        addLog(`⚠️ 文件同步结束，${errCount} 个失败`, "error");
    } else {
        file.progressStatus = "success";
        file.progressLabel = "全部同步成功 🎉";
        file.tasks = [];
        overwrite.dirs = overwrite.dirs.map((d) => ({ ...d, has_update: false, changed: 0 }));
        addLog("✅ 所有文件同步完成！", "success");
        addLog("📜 正在检测是否有新增 SQL 版本文件...", "info");
        await refreshSqlTasks();
    }
    return errCount;
}

// ==================== 数据库升级 ====================
async function runDbExecute(): Promise<number> {
    db.executing = true;
    db.execDone = false;
    db.progressDone = 0;
    db.progressPercent = 0;
    db.progressStatus = "";
    const pending = pendingDiffs.value;
    db.executingTotal = pending.length;
    addLog(`🚀 批量执行数据库升级（共 ${pending.length} 条）...`);
    let errCount = 0;
    for (let i = 0; i < pending.length; i++) {
        const diff = pending[i];
        db.progressLabel = `正在执行：${diff.msg}`;
        try {
            await dbExecute(db.cacheKey, diff._originIndex);
            db.itemStatus[diff._key] = "success";
            addLog(`执行成功：${diff.msg}`, "success");
        } catch (e: any) {
            db.itemStatus[diff._key] = "error";
            addLog(`执行失败：${diff.msg} — ${e.message ?? e}`, "error");
            errCount++;
        }
        db.progressDone = i + 1;
        db.progressPercent = Math.round(((i + 1) / pending.length) * 100);
    }
    db.executing = false;
    db.execDone = true;
    db.progressStatus = errCount > 0 ? "exception" : "success";
    db.progressLabel = errCount > 0 ? `升级完成（${errCount} 项失败）` : "全部升级成功 🎉";
    addLog(
        errCount > 0 ? `⚠️ 数据库升级结束，${errCount} 项失败` : "✅ 数据库升级全部完成！",
        errCount > 0 ? "error" : "success",
    );
    return errCount;
}

// ==================== SQL 批量执行 ====================
async function runSqlBatch(): Promise<number> {
    const pending = pendingSqlTasks.value;
    if (pending.length === 0) return 0;
    sql.batchRunning = true;
    sql.batchDone = false;
    sql.progressDone = 0;
    sql.progressTotal = pending.length;
    sql.progressPercent = 0;
    sql.progressStatus = "";
    addLog(`🚀 批量执行 SQL 升级（共 ${pending.length} 个文件）...`);
    let errCount = 0;
    for (let i = 0; i < pending.length; i++) {
        const task = pending[i];
        sql.progressLabel = `正在执行：${task.file}`;
        try {
            const res = (await sqlExecute(task.file)) as any;
            sql.taskStatus[task.file] = "success";
            if (res?.skipped) addLog(`已跳过：${task.file}（${res.reason ?? "表已存在"}）`, "info");
            else addLog(`执行成功：${task.file}（共 ${res?.count ?? 0} 条语句）`, "success");
        } catch (e: any) {
            sql.taskStatus[task.file] = "error";
            addLog(`执行失败：${task.file} — ${e.message ?? e}`, "error");
            errCount++;
        }
        sql.progressDone = i + 1;
        sql.progressPercent = Math.round(((i + 1) / pending.length) * 100);
    }
    sql.batchRunning = false;
    sql.batchDone = true;
    sql.progressStatus = errCount > 0 ? "exception" : "success";
    sql.progressLabel = errCount > 0 ? `执行完成（${errCount} 个失败）` : "全部执行成功 🎉";
    addLog(
        errCount > 0 ? `⚠️ SQL 升级结束，${errCount} 个失败` : "✅ SQL 版本升级全部完成！",
        errCount > 0 ? "error" : "success",
    );
    return errCount;
}

// ==================== 单项操作 ====================
function onRemoveFileTask(item: any) {
    file.tasks = file.tasks.filter((t) => t.file !== item.file);
    delete file.taskStatus[item.file];
    delete file.taskSyncing[item.file];
    addLog(`已从列表移除文件：${item.file}`, "warning");
}
async function onSingleFileSync(item: any) {
    if (file.taskSyncing[item.file]) return;
    file.taskSyncing[item.file] = true;
    file.taskStatus[item.file] = "";
    addLog(`开始同步单文件：${item.file}`);
    try {
        await fileSync(item.file);
        file.taskStatus[item.file] = "success";
        addLog(`同步成功：${item.file}`, "success");
        addLog("📜 正在检测是否有新增 SQL 版本文件...", "info");
        await refreshSqlTasks();
    } catch (e: any) {
        file.taskStatus[item.file] = "error";
        addLog(`同步失败：${item.file} — ${e.message ?? e}`, "error");
    } finally {
        file.taskSyncing[item.file] = false;
    }
}
async function onViewDiff(item: any) {
    diffState.file = item.file;
    diffState.hunks = [];
    diffState.visible = true;
    diffState.loading = true;
    diffState.loadingFile = item.file;
    try {
        const res = (await fileDiff(item.file)) as any;
        diffState.hunks = res?.hunks ?? [];
    } catch (e: any) {
        diffState.visible = false;
        feedback.msgError(e?.message ?? "获取差异详情失败");
    } finally {
        diffState.loading = false;
        diffState.loadingFile = "";
    }
}
function onRemoveDbDiff(item: DbDiffItem) {
    db.diffs = db.diffs.filter((d) => d._key !== item._key);
    delete db.itemStatus[item._key];
    delete db.itemExecuting[item._key];
    addLog(`已从列表移除数据库差异：${item.msg}`, "warning");
}
async function onSingleDbExecute(item: DbDiffItem) {
    if (db.itemExecuting[item._key]) return;
    db.itemExecuting[item._key] = true;
    db.itemStatus[item._key] = "";
    addLog(`▶ 单独执行：${item.msg}`);
    try {
        await dbExecute(db.cacheKey, item._originIndex);
        db.itemStatus[item._key] = "success";
        addLog(`执行成功：${item.msg}`, "success");
    } catch (e: any) {
        db.itemStatus[item._key] = "error";
        addLog(`执行失败：${item.msg} — ${e.message ?? e}`, "error");
    } finally {
        db.itemExecuting[item._key] = false;
    }
}
async function onDbExecute() {
    db.confirmVisible = false;
    await runDbExecute();
}
function onRemoveSqlTask(item: SqlTask) {
    sql.tasks = sql.tasks.filter((t) => t.file !== item.file);
    delete sql.taskStatus[item.file];
    delete sql.taskRunning[item.file];
    addLog(`已从列表移除 SQL 文件：${item.file}`, "warning");
}
async function onSqlSingleExecute(item: SqlTask) {
    if (sql.taskRunning[item.file]) return;
    sql.taskRunning[item.file] = true;
    sql.taskStatus[item.file] = "";
    addLog(`▶ 开始执行：${item.file}`);
    try {
        const res = (await sqlExecute(item.file)) as any;
        sql.taskStatus[item.file] = "success";
        if (res?.skipped) addLog(`已跳过：${item.file}（${res.reason ?? "表已存在"}）`, "info");
        else addLog(`执行成功：${item.file}（共 ${res?.count ?? 0} 条语句）`, "success");
    } catch (e: any) {
        sql.taskStatus[item.file] = "error";
        addLog(`执行失败：${item.file} — ${e.message ?? e}`, "error");
    } finally {
        sql.taskRunning[item.file] = false;
    }
}

async function onSingleDirOverwrite(ow: OverwriteDir) {
    if (overwrite.dirStatus[ow.dir] === "running") return;
    overwrite.dirStatus[ow.dir] = "running";
    addLog(`🗂️ 开始覆盖目录：${ow.dir}（${ow.changed} 个变更文件）`);
    try {
        const res = (await overwriteDir(ow.dir)) as any;
        const detail = res.details?.[0] ?? { count: 0, errors: [] };
        if ((detail.errors ?? []).length > 0) {
            detail.errors.forEach((e: any) => addLog(`覆盖失败：${e.file} — ${e.msg}`, "error"));
            overwrite.dirStatus[ow.dir] = "error";
        } else {
            overwrite.dirStatus[ow.dir] = "success";
            const idx = overwrite.dirs.findIndex((d) => d.dir === ow.dir);
            if (idx !== -1) overwrite.dirs[idx] = { ...overwrite.dirs[idx], has_update: false, changed: 0 };
            addLog(`✅ ${ow.dir} 覆盖完成（共 ${detail.count ?? 0} 个文件）`, "success");
        }
    } catch (e: any) {
        overwrite.dirStatus[ow.dir] = "error";
        addLog(`❌ ${ow.dir} 覆盖失败：${e.message ?? e}`, "error");
    }
}

// ==================== 下载 ====================
const handleDownloadMiniProgramPackage = () =>
    window.open(`https://rpaimai.imai.work/download/mini_program.zip`, "_blank");
const downloadSourceCode = () => window.open(`https://rpaimai.imai.work/download/source_code.zip`, "_blank");
</script>

<style scoped>
.progress-stripe {
    background-image: linear-gradient(
        90deg,
        transparent 25%,
        rgba(255, 255, 255, 0.18) 25%,
        rgba(255, 255, 255, 0.18) 50%,
        transparent 50%,
        transparent 75%,
        rgba(255, 255, 255, 0.18) 75%
    );
    background-size: 20px 20px;
    animation: stripe 0.8s linear infinite;
}
@keyframes stripe {
    from {
        background-position: 0 0;
    }
    to {
        background-position: 20px 0;
    }
}
@keyframes blink {
    50% {
        opacity: 0;
    }
}

.mini-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 5px;
    font-size: 11.5px;
    font-weight: 600;
    border: 1px solid transparent;
    cursor: pointer;
    transition: all 0.12s;
    white-space: nowrap;
}
.mini-btn:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}

.log-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.log-scrollbar::-webkit-scrollbar-thumb {
    background: #334155;
    border-radius: 2px;
}

.spin-ring {
    display: inline-block;
    width: 14px;
    height: 14px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
}
.spin-ring.sm {
    width: 12px;
    height: 12px;
}
.spin-ring.xs {
    width: 10px;
    height: 10px;
    border-width: 1.5px;
}
@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* v-html 公告内容样式 */
:deep(.notice-item b),
:deep(.notice-item strong) {
    font-weight: 700;
    color: #111827;
}
:deep(.notice-item a) {
    color: #3b82f6;
    text-decoration: underline;
}
:deep(.notice-item code) {
    font-family: monospace;
    font-size: 11.5px;
    background: #f3f4f6;
    padding: 1px 5px;
    border-radius: 4px;
    color: #7c3aed;
}

:deep(.confirm-dialog .el-dialog__header) {
    padding: 20px 24px 0;
    font-weight: 700;
}
:deep(.confirm-dialog .el-dialog__body) {
    padding: 16px 24px;
}
:deep(.confirm-dialog .el-dialog__footer) {
    padding: 0 24px 20px;
}
:deep(.diff-drawer .el-drawer__header) {
    padding: 13px 20px;
    border-bottom: 1px solid #e5e7eb;
    margin-bottom: 0;
}
:deep(.diff-drawer .el-drawer__body) {
    padding: 0;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
</style>
