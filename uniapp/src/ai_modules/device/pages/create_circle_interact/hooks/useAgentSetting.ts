import type { CircleFormData } from "./types";

export function useAgentSetting(formData: CircleFormData) {
    const showChooseAgent = ref(false);
    const chooseAgentRef = shallowRef();

    const handleChooseAgentConfirm = (agent: any) => {
        formData.robot_id = agent.id;
        formData.robot_name = agent.name;
        showChooseAgent.value = false;
    };

    return {
        showChooseAgent,
        chooseAgentRef,
        handleChooseAgentConfirm,
    };
}
