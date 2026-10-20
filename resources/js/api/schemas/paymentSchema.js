export const bankAccountSchema = {
  required: ['bankName', 'accountNumber', 'accountHolder'],
  properties: {
    id: { type: 'string' },
    bankName: { type: 'string', minLength: 1 },
    accountNumber: { type: 'string', minLength: 1 },
    accountHolder: { type: 'string', minLength: 1 },
    logoUrl: { type: 'string' }
  }
}

export const giftSchema = {
  required: ['senderName', 'amount'],
  properties: {
    id: { type: 'string' },
    senderName: { type: 'string', minLength: 1 },
    amount: { type: 'number', min: 0 },
    method: { type: 'string' },
    bankName: { type: 'string' },
    message: { type: 'string' },
    createdAt: { type: 'string' }
  }
}
