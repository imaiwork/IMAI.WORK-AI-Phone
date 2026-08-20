import { ref } from 'vue'

export type SelectedChatModel = {
    model_id?: string | number
    model_sub_id?: string | number
}

const STORAGE_KEY = 'imai_selected_chat_model'

const selectedChatModel = ref<SelectedChatModel>(readStoredModel())

function readStoredModel(): SelectedChatModel {
    if (!import.meta.client) return {}
    try {
        const raw = sessionStorage.getItem(STORAGE_KEY)
        if (!raw) return {}
        const parsed = JSON.parse(raw)
        return {
            model_id: parsed?.model_id,
            model_sub_id: parsed?.model_sub_id
        }
    } catch {
        return {}
    }
}

export function useSelectedChatModel() {
    function setSelectedChatModel(model: SelectedChatModel) {
        selectedChatModel.value = {
            model_id: model?.model_id,
            model_sub_id: model?.model_sub_id
        }
        if (!import.meta.client) return
        try {
            if (model?.model_id == null || model.model_id === '') {
                sessionStorage.removeItem(STORAGE_KEY)
                return
            }
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify(selectedChatModel.value))
        } catch {
            /* ignore quota / private mode */
        }
    }

    return {
        selectedChatModel,
        setSelectedChatModel
    }
}
