import { test as base, expect } from '@playwright/test'

export const test = base.extend<{ authenticatedPage: typeof base }>({})

export async function login(
    page: import('@playwright/test').Page,
    email = 'test@example.com',
    password = 'password',
) {
    await page.goto('/login')
    await page.fill('input[type="email"]', email)
    await page.fill('input[type="password"]', password)
    await page.click('button[type="submit"]')
    await expect(page).toHaveURL('/')
}

export { expect }
