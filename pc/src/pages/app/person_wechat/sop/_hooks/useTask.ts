import { sopFlowLists } from "@/api/person_wechat";

// 资源图片
import CreateImg from "../_assets/images/task_step_create.png";
import ContentImg from "../_assets/images/task_step_content.png";
import PostImg from "../_assets/images/task_step_post.png";

const taskFormData = reactive({
    id: "",
    name: "",
    content: [],
    people: [],
});

const flowLists = ref<any[]>([]);
export default function useTask() {
    // 查询流程
    const getFlowLists = async () => {
        const { lists } = await sopFlowLists({ page_type: 0 });
        flowLists.value = lists;
    };

    return {
        resource: {
            CreateImg,
            ContentImg,
            PostImg,
        },
        taskFormData,
        flowLists,
        getFlowLists,
    };
}
