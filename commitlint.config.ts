export default {
    extends: ['@commitlint/config-conventional'],
    rules: {
        'scope-enum': [
            2,
            'always',
            ['api', 'frontend', 'docker', 'e2e', 'ci', 'deps'],
        ],
    },
}
