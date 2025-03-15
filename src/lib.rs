use anchor_lang::prelude::*;
use anchor_spl::token::{self, Token, Mint, TokenAccount, Transfer};

declare_id!("HFXc8g3dmAbPz7ad7JgA6rEYN8Jjp5wLioKQLit4uRAY");

#[program]
pub mod cb_game {
    use super::*;

    pub fn initialize_config(
        ctx: Context<InitializeConfig>,
        shield_price: u64,
        referral_reward: u64,
        continue_price: u64,
    ) -> Result<()> {
        let config = &mut ctx.accounts.config;
        config.shield_price = shield_price;
        config.referral_reward = referral_reward;
        config.continue_price = continue_price;
        msg!("Configuration initialized.");
        Ok(())
    }

    // Step 2: Initialize the SPL token accounts.
    pub fn initialize_token_accounts(ctx: Context<InitializeTokenAccounts>) -> Result<()> {
        msg!(
            "Token accounts initialized: treasury {:?}, vault {:?}",
            ctx.accounts.treasury_account.key(),
            ctx.accounts.vault_account.key()
        );
        Ok(())
    }

    // The rest of your instructions remain unchanged.
    pub fn initialize_session(ctx: Context<InitializeSession>, referrer: Option<Pubkey>) -> Result<()> {
        let session_data = &mut ctx.accounts.session_data;
        session_data.player = *ctx.accounts.player.key;
        if let Some(referrer_key) = referrer {
            require!(referrer_key != session_data.player, CustomError::InvalidReferrer);
        }
        session_data.referrer = referrer;
        session_data.shields_purchased = 0;
        session_data.earned_cb = 0;
        session_data.pending_rewards = 0;
        Ok(())
    }

    pub fn purchase_shield(ctx: Context<PurchaseShield>, amount: u64) -> Result<()> {
        let player = &ctx.accounts.player;
        let player_token_account = &mut ctx.accounts.player_token_account;
        let treasury_account = &mut ctx.accounts.treasury_account;
        let session_data = &mut ctx.accounts.session_data;
        let config = &ctx.accounts.config;
    
        require!(amount >= config.shield_price, CustomError::InsufficientBalance);
    
        // 🔹 Derive the Treasury PDA
        let (_treasury_authority, treasury_bump) =
            Pubkey::find_program_address(&[b"treasury-authority"], ctx.program_id);
        let treasury_seeds = &[b"treasury-authority".as_ref(), &[treasury_bump]];
    
        // ✅ Transfer CB tokens from Player to Treasury PDA
        let cpi_accounts = Transfer {
            from: player_token_account.to_account_info(),
            to: treasury_account.to_account_info(),
            authority: player.to_account_info(), // ✅ Player signs
        };
    
        let cpi_context = CpiContext::new_with_signer(
            ctx.accounts.token_program.to_account_info(),
            cpi_accounts,
            &[treasury_seeds], // ✅ Use PDA as signer
        );
    
        token::transfer(cpi_context, config.shield_price)?;
    
        // ✅ Deduct CB tokens from session data
        session_data.earned_cb = session_data
            .earned_cb
            .checked_sub(config.shield_price)
            .ok_or(CustomError::InsufficientBalance)?;
    
        emit!(ShieldPurchasedEvent {
            player: player.key(),
            amount: config.shield_price,
        });
        Ok(())
    }
    
    pub fn pay_to_continue(ctx: Context<PayToContinue>) -> Result<()> {
        let player = &ctx.accounts.player;
        let player_token_account = &mut ctx.accounts.player_token_account;
        let treasury_account = &mut ctx.accounts.treasury_account;
        let session_data = &mut ctx.accounts.session_data;
        let config = &ctx.accounts.config;
    
        // 🔹 Ensure the player has enough tokens to continue
        require!(
            session_data.earned_cb >= config.continue_price,
            CustomError::InsufficientBalance
        );
    
        // 🔹 Derive the Treasury PDA
        let (_treasury_authority, treasury_bump) =
            Pubkey::find_program_address(&[b"treasury-authority"], ctx.program_id);
        let treasury_seeds = &[b"treasury-authority".as_ref(), &[treasury_bump]];
    
        // ✅ Transfer CB tokens from Player to Treasury PDA
        let cpi_accounts = Transfer {
            from: player_token_account.to_account_info(),
            to: treasury_account.to_account_info(),
            authority: ctx.accounts.player.to_account_info(), // ✅ Player signs
        };
    
        let cpi_context = CpiContext::new_with_signer(
            ctx.accounts.token_program.to_account_info(),
            cpi_accounts,
            &[treasury_seeds], // ✅ Ensure PDA signs transaction
        );
    
        token::transfer(cpi_context, config.continue_price)?;
    
        // ✅ Deduct CB tokens from session data
        session_data.earned_cb = session_data
            .earned_cb
            .checked_sub(config.continue_price)
            .ok_or(CustomError::InsufficientBalance)?;
    
        msg!("✅ Player {} paid {} CB tokens to continue.", player.key(), config.continue_price);
    
        Ok(())
    }
    
    pub fn withdraw_tokens(ctx: Context<WithdrawTokens>, amount: u64) -> Result<()> {
        let session_data = &mut ctx.accounts.session_data;
        let player_token_account = &ctx.accounts.player_token_account;
        let vault_account = &ctx.accounts.vault_account;
    
        require!(
            amount <= session_data.earned_cb,
            CustomError::InsufficientBalance
        );
    
        // 🔹 Derive the vault authority PDA
        let (_vault_authority, vault_bump) =
            Pubkey::find_program_address(&[b"vault-authority"], ctx.program_id);
        let vault_seeds = &[b"vault-authority".as_ref(), &[vault_bump]];
    
        // ✅ Transfer CB tokens from Vault PDA to Player
        let cpi_accounts = Transfer {
            from: vault_account.to_account_info(),
            to: player_token_account.to_account_info(),
            authority: ctx.accounts.vault_authority.to_account_info(), // ✅ PDA as authority
        };
    
        let cpi_context = CpiContext::new_with_signer(
            ctx.accounts.token_program.to_account_info(),
            cpi_accounts,
            &[vault_seeds], // ✅ Use PDA as signer
        );
    
        token::transfer(cpi_context, amount)?;
    
        // ✅ Deduct CB tokens from session data
        session_data.earned_cb = session_data
            .earned_cb
            .checked_sub(amount)
            .ok_or(CustomError::InsufficientBalance)?;
    
        emit!(WithdrawEvent {
            player: session_data.player,
            amount,
        });
    
        msg!("✅ {} CB Tokens Withdrawn to {}", amount, session_data.player);
        
        Ok(())
    }
    
    pub fn admin_withdraw_from_vault(ctx: Context<AdminWithdraw>, amount: u64) -> Result<()> {
        let cpi_accounts = Transfer {
            from: ctx.accounts.vault_account.to_account_info(),
            to: ctx.accounts.admin_token_account.to_account_info(),
            authority: ctx.accounts.admin_authority.to_account_info(),
        };
        let cpi_context =
            CpiContext::new(ctx.accounts.token_program.to_account_info(), cpi_accounts);
        token::transfer(cpi_context, amount)?;
        Ok(())
    }

    pub fn admin_deposit_to_vault(ctx: Context<AdminDeposit>, amount: u64) -> Result<()> {
        let cpi_accounts = Transfer {
            from: ctx.accounts.admin_token_account.to_account_info(),
            to: ctx.accounts.vault_account.to_account_info(),
            authority: ctx.accounts.admin_authority.to_account_info(),
        };
        let cpi_context =
            CpiContext::new(ctx.accounts.token_program.to_account_info(), cpi_accounts);
        token::transfer(cpi_context, amount)?;
        Ok(())
    }

    pub fn check_balance(ctx: Context<CheckBalance>) -> Result<u64> {
        let session_data = &ctx.accounts.session_data;
        msg!("Earned CB balance: {}", session_data.earned_cb);
        Ok(session_data.earned_cb)
    }

    pub fn update_referrer(ctx: Context<UpdateReferrer>, new_referrer: Pubkey) -> Result<()> {
        let session_data = &mut ctx.accounts.session_data;
        require!(
            session_data.player != new_referrer,
            CustomError::InvalidReferrer
        );
        session_data.referrer = Some(new_referrer);
        msg!(
            "Referrer for player {:?} updated to {:?}",
            session_data.player,
            new_referrer
        );
        Ok(())
    }
}


// Account data structures

#[account]
pub struct SessionData {
    pub shields_purchased: u8,
    pub player: Pubkey,
    pub referrer: Option<Pubkey>,
    pub earned_cb: u64,
    pub pending_rewards: u64,
}

#[account]
pub struct Config {
    pub shield_price: u64,
    pub referral_reward: u64,
    pub continue_price: u64,
}

// Context definitions

// Context for configuration initialization.
#[derive(Accounts)]
pub struct InitializeConfig<'info> {
    #[account(init, payer = payer, space = 8 + 24)]
    pub config: Account<'info, Config>,
    #[account(mut)]
    pub payer: Signer<'info>,
    pub system_program: Program<'info, System>,
}

// Context for token account initialization.
// Note the use of Box to reduce stack usage.
#[derive(Accounts)]
pub struct InitializeTokenAccounts<'info> {
    /// The mint for your CB token (must be already initialized).
    pub cb_mint: Account<'info, Mint>,

    #[account(
        init,
        payer = payer,
        token::mint = cb_mint,
        token::authority = treasury_authority,
        seeds = [b"treasury-token-account"],
        bump
    )]
    pub treasury_account: Box<Account<'info, TokenAccount>>,

    #[account(
        init,
        payer = payer,
        token::mint = cb_mint,
        token::authority = vault_authority,
        seeds = [b"vault-token-account"],
        bump
    )]
    pub vault_account: Box<Account<'info, TokenAccount>>,

    /// CHECK: This PDA is used as the authority for the treasury token account.
    #[account(seeds = [b"treasury-authority"], bump)]
    pub treasury_authority: UncheckedAccount<'info>,

    /// CHECK: This PDA is used as the authority for the vault token account.
    #[account(seeds = [b"vault-authority"], bump)]
    pub vault_authority: UncheckedAccount<'info>,

    #[account(mut)]
    pub payer: Signer<'info>,
    pub system_program: Program<'info, System>,
    pub token_program: Program<'info, Token>,
}

// Context for initializing a session.
#[derive(Accounts)]
pub struct InitializeSession<'info> {
    #[account(init, payer = player, space = 8 + 1 + 32 + 33 + 8 + 8)]
    pub session_data: Account<'info, SessionData>,
    #[account(mut)]
    pub player: Signer<'info>,
    pub system_program: Program<'info, System>,
}

// Context for purchasing a shield.
#[derive(Accounts)]
pub struct PurchaseShield<'info> {
    #[account(mut)]
    pub player_token_account: Account<'info, TokenAccount>,
    #[account(mut)]
    pub treasury_account: Box<Account<'info, TokenAccount>>,
    #[account(mut, has_one = player)]
    pub session_data: Account<'info, SessionData>,
    pub config: Account<'info, Config>,
    pub player: Signer<'info>,
    pub token_program: Program<'info, Token>,
}

#[derive(Accounts)]
pub struct WithdrawTokens<'info> {
    #[account(mut)]
    pub vault_account: Box<Account<'info, TokenAccount>>,

    #[account(mut)]
    pub player_token_account: Account<'info, TokenAccount>,

    #[account(mut, has_one = player)]
    pub session_data: Account<'info, SessionData>,

    /// CHECK: This is the PDA that controls the vault.
    #[account(seeds = [b"vault-authority"], bump)]
    pub vault_authority: UncheckedAccount<'info>,

    pub token_program: Program<'info, Token>,
    pub player: Signer<'info>,
}



// Context for an admin depositing tokens to the vault.
#[derive(Accounts)]
pub struct AdminDeposit<'info> {
    #[account(mut)]
    pub admin_token_account: Account<'info, TokenAccount>,
    #[account(mut)]
    pub vault_account: Box<Account<'info, TokenAccount>>,
    pub admin_authority: Signer<'info>,
    pub token_program: Program<'info, Token>,
}

// Context for an admin withdrawing tokens from the vault.
#[derive(Accounts)]
pub struct AdminWithdraw<'info> {
    #[account(mut)]
    pub vault_account: Box<Account<'info, TokenAccount>>,
    #[account(mut)]
    pub admin_token_account: Account<'info, TokenAccount>,
    pub admin_authority: Signer<'info>,
    pub token_program: Program<'info, Token>,
}

// Context for checking balance.
#[derive(Accounts)]
pub struct CheckBalance<'info> {
    #[account(mut, has_one = player)]
    pub session_data: Account<'info, SessionData>,
    pub player: Signer<'info>,
}

// Context for updating the referrer.
#[derive(Accounts)]
pub struct UpdateReferrer<'info> {
    #[account(mut, has_one = player)]
    pub session_data: Account<'info, SessionData>,
    pub player: Signer<'info>,
}

// Event definitions

#[event]
pub struct WithdrawEvent {
    pub player: Pubkey,
    pub amount: u64,
}

#[event]
pub struct ShieldPurchasedEvent {
    pub player: Pubkey,
    pub amount: u64,
}

// Custom error codes

#[error_code]
pub enum CustomError {
    #[msg("Invalid shield amount.")]
    InvalidShieldAmount,
    #[msg("Shield purchase limit exceeded.")]
    ShieldLimitExceeded,
    #[msg("Insufficient balance.")]
    InsufficientBalance,
    #[msg("Invalid referrer.")]
    InvalidReferrer,
    #[msg("No pending rewards to claim.")]
    NoPendingRewards,
}

#[derive(Accounts)]
pub struct PayToContinue<'info> {
    #[account(mut)]
    pub player_token_account: Account<'info, TokenAccount>,

    #[account(
        mut,
        constraint = treasury_account.owner == *treasury_authority.key
    )]
    pub treasury_account: Box<Account<'info, TokenAccount>>,

    #[account(mut, has_one = player)]
    pub session_data: Account<'info, SessionData>,

    #[account(seeds = [b"treasury-authority"], bump)]
    pub treasury_authority: UncheckedAccount<'info>,

    pub config: Account<'info, Config>,
    pub player: Signer<'info>,
    pub token_program: Program<'info, Token>,
}
