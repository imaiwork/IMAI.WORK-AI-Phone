<template>
	<div>
		<el-card class="!border-none" shadow="never">
			<el-form
				ref="formRef"
				class="mb-[-16px]"
				:model="queryParams"
				:inline="true">
				<el-form-item label="用户信息">
					<el-input
						class="w-[280px]"
						v-model="queryParams.user"
						placeholder="请输入用户昵称"
						clearable
						@keyup.enter="resetPage" />
				</el-form-item>
				<el-form-item label="岗位名称">
					<el-input
						class="w-[280px]"
						v-model="queryParams.name"
						placeholder="请输入岗位名称"
						clearable
						@keyup.enter="resetPage" />
				</el-form-item>
				<el-form-item label="创建时间">
					<daterange-picker
						v-model:startTime="queryParams.start_time"
						v-model:endTime="queryParams.end_time" />
				</el-form-item>
				<el-form-item>
					<el-button type="primary" @click="resetPage"
						>查询</el-button
					>
					<el-button @click="resetParams">重置</el-button>
				</el-form-item>
			</el-form>
		</el-card>
		<el-card class="!border-none mt-4" shadow="never">
			<el-table
				size="large"
				v-loading="pager.loading"
				:data="pager.lists">
				<el-table-column label="ID" prop="id" min-width="80" />
				<el-table-column
					prop="user.nickname"
					label="面试者"
					min-width="140"
					show-overflow-tooltip></el-table-column>
				<el-table-column
					label="面试岗位"
					prop="job.name"
					min-width="180"
					show-overflow-tooltip />
				<el-table-column
					label="招聘公司"
					prop="job.company"
					min-width="180"
					show-overflow-tooltip />
				<el-table-column
					label="创建用户"
					prop="create_user.nickname"
					min-width="140"
					show-overflow-tooltip />
				<el-table-column
					label="面试时间"
					prop="create_time"
					min-width="180" />
				<el-table-column label="操作" width="100" fixed="right">
					<template #default="{ row }">
						<el-button
							type="primary"
							link
							@click="handleFeedback(row.content)">
							查看
						</el-button>
					</template>
				</el-table-column>
			</el-table>
			<div class="flex justify-end mt-4">
				<pagination v-model="pager" @change="getLists" />
			</div>
		</el-card>
	</div>
</template>
<script lang="ts" setup>
import {
	getInterviewFeedbackList,
	deleteInterviewFeedback,
} from "@/api/ai_application/interview/feedback";
import { formatAudioTime } from "@/utils/util";
import { usePaging } from "@/hooks/usePaging";
import { getRoutePath } from "@/router";
import feedback from "@/utils/feedback";
import { ElMessageBox } from "element-plus";

const queryParams = reactive({
	name: "",
	user: "",
	start_time: "",
	end_time: "",
});

const { pager, getLists, resetPage, resetParams } = usePaging({
	fetchFun: getInterviewFeedbackList,
	params: queryParams,
});

const handleFeedback = (content: string) => {
	ElMessageBox.alert(content, "面试反馈", {
		confirmButtonText: "确定",
		showCancelButton: false,
	});
};

getLists();
</script>
