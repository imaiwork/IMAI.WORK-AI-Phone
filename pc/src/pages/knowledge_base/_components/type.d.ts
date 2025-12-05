import { KnTypeEnum } from "../_enums";

export interface CreateFormData {
    name: string;
    description: string;
    cover: string;
    type?: KnTypeEnum;
}
