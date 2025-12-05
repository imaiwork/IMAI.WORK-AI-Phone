<template>
	<div>
		<el-card class="!border-none" shadow="never">
			<el-form
				ref="formRef"
				class="mb-[-16px]"
				:model="queryParams"
				:inline="true">
				<el-form-item label="内容搜索">
					<el-input
						class="w-[200px]"
						v-model="queryParams.title"
						placeholder="请输入内容关键词"
						clearable
						@keyup.enter="resetPage" />
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
			<div>
				<el-button
					v-perms="['ai_application.sd.prompt/add']"
					type="primary"
					@click="openPop('add')">
					<template #icon>
						<icon name="el-icon-Plus" />
					</template>
					新增
				</el-button>
			</div>
			<el-table
				size="large"
				class="mt-4"
				v-loading="pager.loading"
				:data="pager.lists">
				<el-table-column label="封面" min-width="100">
					<template #default="{ row }">
						<image-contain
							:src="row.pic"
							:width="50"
							:height="50"
							:preview-src-list="[row.pic]"
							preview-teleported
							fit="cover" />
					</template>
				</el-table-column>
				<el-table-column
					label="关键词"
					prop="title"
					min-width="180"
					show-overflow-tooltip />
				<el-table-column
					label="所属分类"
					prop="category.title"
					min-width="120" />
				<el-table-column
					label="状态"
					min-width="100"
					v-perms="['ai_application.sd.prompt/status']">
					<template #default="{ row }">
						<el-switch
							v-model="row.status"
							:active-value="1"
							:inactive-value="0"
							@change="changeStatus(row.id)" />
					</template>
				</el-table-column>
				<el-table-column label="排序" prop="sort" min-width="120" />
				<el-table-column
					label="创建时间"
					prop="create_time"
					show-overflow-tooltip
					min-width="180" />
				<el-table-column label="操作" width="150" fixed="right">
					<template #default="{ row }">
						<el-button
							v-perms="['ai_application.sd.prompt/edit']"
							type="primary"
							link
							@click="openPop('edit', row)">
							编辑
						</el-button>
						<el-button
							v-perms="['ai_application.sd.prompt/delete']"
							type="danger"
							link
							@click="handleDelete([row.id])">
							删除
						</el-button>
					</template>
				</el-table-column>
			</el-table>
			<div class="flex justify-end mt-4">
				<pagination v-model="pager" @change="getLists" />
			</div>
		</el-card>
		<edit-popup v-if="showEdit" ref="editRef" @success="getLists" />
	</div>
</template>
<script lang="ts" setup name="drawPrompt">
import { usePaging } from "@/hooks/usePaging";
import {
	getPromptList,
	delPrompt,
	editPromptStatus,
} from "@/api/ai_application/draw/draw_sd";
import EditPopup from "./edit.vue";
import feedback from "@/utils/feedback";

//弹框ref
const editRef = shallowRef<InstanceType<typeof EditPopup>>();
const showEdit = ref(false);
//搜索参数
const queryParams = reactive({
	title: "",
});

//打开弹框
const openPop = async (type: string, value: any = {}) => {
	showEdit.value = true;
	await nextTick();
	editRef.value?.open(type, value);
};

//删除
const handleDelete = async (id: number[]) => {
	await feedback.confirm("确定要删除？");
	await delPrompt({ id });
	getLists();
};

//修改状态
const changeStatus = (id: any) => {
	editPromptStatus({ id });
};

const { pager, getLists, resetPage, resetParams } = usePaging({
	fetchFun: getPromptList,
	params: queryParams,
});

getLists();
</script>
