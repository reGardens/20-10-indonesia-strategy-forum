import { describe, it, expect, beforeEach } from 'vitest'
import { paymentService } from '../../../src/api/services/paymentService.js'

const TEST_SLUG = 'test-wedding'

describe('paymentService', () => {
  beforeEach(() => {
    localStorage.clear()
  })

  describe('bank accounts', () => {
    it('returns empty array initially', async () => {
      const result = await paymentService.getBankAccounts(TEST_SLUG)
      expect(result).toEqual([])
    })

    it('adds bank account', async () => {
      const account = await paymentService.addBankAccount(TEST_SLUG, {
        bankName: 'BCA',
        accountNumber: '1234567890',
        accountHolder: 'Budi Santoso'
      })
      expect(account.id).toBeDefined()
      expect(account.bankName).toBe('BCA')
    })

    it('updates bank account', async () => {
      const account = await paymentService.addBankAccount(TEST_SLUG, { bankName: 'BCA', accountNumber: '123' })
      const updated = await paymentService.updateBankAccount(TEST_SLUG, account.id, { accountNumber: '456' })
      expect(updated.accountNumber).toBe('456')
      expect(updated.bankName).toBe('BCA')
    })

    it('deletes bank account', async () => {
      const account = await paymentService.addBankAccount(TEST_SLUG, { bankName: 'BCA' })
      expect(await paymentService.deleteBankAccount(TEST_SLUG, account.id)).toBe(true)
      const all = await paymentService.getBankAccounts(TEST_SLUG)
      expect(all).toHaveLength(0)
    })

    it('returns false when deleting non-existent account', async () => {
      expect(await paymentService.deleteBankAccount(TEST_SLUG, 'nonexistent')).toBe(false)
    })
  })

  describe('gifts', () => {
    it('returns empty array initially', async () => {
      const result = await paymentService.getGifts(TEST_SLUG)
      expect(result).toEqual([])
    })

    it('adds gift', async () => {
      const gift = await paymentService.addGift(TEST_SLUG, {
        senderName: 'Ahmad',
        amount: 500000,
        method: 'transfer_bank'
      })
      expect(gift.id).toBeDefined()
      expect(gift.senderName).toBe('Ahmad')
      expect(gift.amount).toBe(500000)
    })

    it('deletes gift', async () => {
      const gift = await paymentService.addGift(TEST_SLUG, { senderName: 'Ahmad', amount: 100000 })
      expect(await paymentService.deleteGift(TEST_SLUG, gift.id)).toBe(true)
    })
  })

  describe('getTotal', () => {
    it('returns 0 when no gifts', async () => {
      const total = await paymentService.getTotal(TEST_SLUG)
      expect(total).toBe(0)
    })

    it('sums all gift amounts', async () => {
      await paymentService.addGift(TEST_SLUG, { senderName: 'A', amount: 500000 })
      await paymentService.addGift(TEST_SLUG, { senderName: 'B', amount: 300000 })
      await paymentService.addGift(TEST_SLUG, { senderName: 'C', amount: 200000 })
      const total = await paymentService.getTotal(TEST_SLUG)
      expect(total).toBe(1000000)
    })
  })

  describe('data isolation', () => {
    it('bank accounts and gifts are stored together but independent', async () => {
      await paymentService.addBankAccount(TEST_SLUG, { bankName: 'BCA' })
      await paymentService.addGift(TEST_SLUG, { senderName: 'Ahmad', amount: 100000 })
      const accounts = await paymentService.getBankAccounts(TEST_SLUG)
      const gifts = await paymentService.getGifts(TEST_SLUG)
      expect(accounts).toHaveLength(1)
      expect(gifts).toHaveLength(1)
    })
  })
})
