<?php

return [
    'welcome' => 'Bem-vindo ao nosso aplicativo!',
    'hello' => 'Olá :name!',
    'goodbye' => 'Tchau!',
    'success' => 'Operação realizada com sucesso!',
    'error' => 'Ocorreu um erro. Tente novamente.',
    'not_found' => 'Recurso não encontrado.',
    'unauthorized' => 'Você não está autorizado a acessar este recurso.',
    'forbidden' => 'Acesso negado.',
    'validation_error' => 'Verifique os dados informados e tente novamente.',
    'save' => 'Salvar',
    'cancel' => 'Cancelar',
    'edit' => 'Editar',
    'delete' => 'Excluir',
    'confirm_delete' => 'Tem certeza de que deseja excluir este item?',
    'created_at' => 'Criado em',
    'updated_at' => 'Atualizado em',
    'updated_success' => ':attribute atualizado com sucesso!',
    'update_failed' => 'Falha ao atualizar :attribute. Tente novamente.',

    // Authentication and authorization
    'token_required' => 'Token de autenticação é obrigatório.',
    'invalid_token' => 'Token de autenticação inválido ou expirado.',
    'user_not_found' => 'Usuário não encontrado.',

    // Account deletion
    'account_deleted_successfully' => 'Sua conta foi excluída permanentemente.',
    'account_deletion_failed' => 'Falha ao excluir sua conta. Tente novamente ou entre em contato com o suporte.',
    'account_deletion_must_be_confirmed' => 'Você deve confirmar que deseja excluir permanentemente sua conta.',

    // Field names for validation
    'password' => 'senha',
    'confirmation' => 'confirmação',

    'social' => [
        'facebook' => 'Facebook',
        'google' => 'Google',
    ],

    'models' => [
        'user' => [
            'name' => 'Usuário',
        ],
    ],

    'validation' => [
        'required' => 'O campo :attribute é obrigatório.',
        'min' => [
            'string' => 'O campo :attribute deve ter pelo menos :min caracteres.',
        ],
        'password_incorrect' => 'A senha fornecida está incorreta.',
    ],
];
