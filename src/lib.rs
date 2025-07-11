use anchor_lang::prelude::*;
use anchor_spl::token::{self, Token, Transfer};

declare_id!("5j1x46tS9NXh6LCkR6MUeV2HyHKSv5nyqJr7JtDbFurD");

#[program]
pub mod cb_game {
    use super::*;

    pub fn initialize(ctx: Context<Initialize>, shield_price: u64, referral_reward: u64, continue_price: u64) -> Result<()> {
        let config = &mut ctx.accounts.config;
        config.shield_price = shield_price;
        config.referral_reward = referral_reward;
        config.continue_price = continue_price;
        msg!(
            "Game initialized with treasury at: {:?}, vault at: {:?}",
            ctx.accounts.treasury_account.key(),
            ctx.accounts.vault_account.key()
        );
        Ok(())
    }

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

    pub fn purchase_shield(ctx: Context<PurchaseShield>) -> Result<()> {
        let config = &ctx.accounts.config;
        let session_data = &mut ctx.accounts.session_data;
        require!(session_data.shields_purchased < 5, CustomError::ShieldLimitExceeded);

        let cpi_accounts = Transfer {
            from: ctx.accounts.player_token_account.to_account_info(),
            to: ctx.accounts.treasury_account.to_account_info(),
            authority: ctx.accounts.player_authority.to_account_info(),
        };
        let cpi_context = CpiContext::new(ctx.accounts.token_program.to_account_info(), cpi_accounts);
        token::transfer(cpi_context, config.shield_price)?;

        session_data.shields_purchased += 1;
        Ok(())
    }

    pub fn earn_cb(ctx: Context<EarnCb>, amount: u64) -> Result<()> {
        let session_data = &mut ctx.accounts.session_data;
        session_data.earned_cb += amount;

        if let Some(referrer_key) = session_data.referrer {
            let referrer_data = &mut ctx.accounts.referrer_session_data;
            require!(referrer_data.player == referrer_key, CustomError::InvalidReferrer);
            referrer_data.pending_rewards += ctx.accounts.config.referral_reward;
        }

        Ok(())
    }

    pub fn claim_referral_rewards(ctx: Context<ClaimReferralRewards>) -> Result<()> {
        let referrer_data = &mut ctx.accounts.referrer_session_data;
        require!(referrer_data.pending_rewards > 0, CustomError::NoPendingRewards);

        let cpi_accounts = Transfer {
            from: ctx.accounts.treasury_account.to_account_info(),
            to: ctx.accounts.referrer_token_account.to_account_info(),
            authority: ctx.accounts.treasury_authority.to_account_info(),
        };
        let cpi_context = CpiContext::new(ctx.accounts.token_program.to_account_info(), cpi_accounts);
        token::transfer(cpi_context, referrer_data.pending_rewards)?;

        referrer_data.pending_rewards = 0;
        Ok(())
    }

    pub fn pay_to_continue(ctx: Context<PayToContinue>) -> Result<()> {
        let config = &ctx.accounts.config;
        let cpi_accounts = Transfer {
            from: ctx.accounts.player_token_account.to_account_info(),
            to: ctx.accounts.treasury_account.to_account_info(),
            authority: ctx.accounts.player_authority.to_account_info(),
        };
        let cpi_context = CpiContext::new(ctx.accounts.token_program.to_account_info(), cpi_accounts);
        token::transfer(cpi_context, config.continue_price)?;
        Ok(())
    }

    pub fn withdraw_tokens(ctx: Context<WithdrawTokens>, amount: u64) -> Result<()> {
        let session_data = &mut ctx.accounts.session_data;
        require!(amount <= session_data.earned_cb, CustomError::InsufficientBalance);

        let vault_key = ctx.accounts.vault_account.key();
        let (_vault_pda, vault_bump) = Pubkey::find_program_address(
            &[b"vault", vault_key.as_ref()],
            ctx.program_id,
        );
        let seeds = &[b"vault", vault_key.as_ref(), &[vault_bump]];
        let signer = &[&seeds[..]];

        let cpi_accounts = Transfer {
            from: ctx.accounts.vault_account.to_account_info(),
            to: ctx.accounts.player_token_account.to_account_info(),
            authority: ctx.accounts.vault_account.to_account_info(),
        };
        let cpi_context = CpiContext::new_with_signer(ctx.accounts.token_program.to_account_info(), cpi_accounts, signer);
        token::transfer(cpi_context, amount)?;

        session_data.earned_cb -= amount;
        Ok(())
    }

    pub fn admin_withdraw_from_vault(ctx: Context<AdminWithdraw>, amount: u64) -> Result<()> {
        let cpi_accounts = Transfer {
            from: ctx.accounts.vault_account.to_account_info(),
            to: ctx.accounts.admin_token_account.to_account_info(),
            authority: ctx.accounts.admin_authority.to_account_info(),
        };
        let cpi_context = CpiContext::new(ctx.accounts.token_program.to_account_info(), cpi_accounts);
        token::transfer(cpi_context, amount)?;
        Ok(())
    }

    pub fn admin_deposit_to_vault(ctx: Context<AdminDeposit>, amount: u64) -> Result<()> {
        let cpi_accounts = Transfer {
            from: ctx.accounts.admin_token_account.to_account_info(),
            to: ctx.accounts.vault_account.to_account_info(),
            authority: ctx.accounts.admin_authority.to_account_info(),
        };
        let cpi_context = CpiContext::new(ctx.accounts.token_program.to_account_info(), cpi_accounts);
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

        require!(session_data.player != new_referrer, CustomError::InvalidReferrer);

        session_data.referrer = Some(new_referrer);
        msg!(
            "Referrer for player {:?} updated to {:?}",
            session_data.player,
            new_referrer
        );

        Ok(())
    }
}

// Custom Account Structures
#[account]
pub struct SessionData {
    pub shields_purchased: u8,
    pub player: Pubkey,
    pub referrer: Option<Pubkey>,
    pub earned_cb: u64,
    pub pending_rewards: u64,
}

#[account]
pub struct CustomTokenAccount {
    pub owner: Pubkey,
    pub amount: u64,
}

#[account]
pub struct Config {
    pub shield_price: u64,
    pub referral_reward: u64,
    pub continue_price: u64,
}

// Context Definitions
#[derive(Accounts)]
pub struct Initialize<'info> {
    #[account(mut)]
    pub payer: Signer<'info>,
    #[account(init, payer = payer, space = 8 + 8 + 8 + 8)]
    pub config: Account<'info, Config>,
    #[account(init, payer = payer, space = 8 + 32 + 8)]
    pub treasury_account: Account<'info, CustomTokenAccount>,
    #[account(init, payer = payer, space = 8 + 32 + 8)]
    pub vault_account: Account<'info, CustomTokenAccount>,
    pub system_program: Program<'info, System>,
    pub token_program: Program<'info, Token>,
}

#[derive(Accounts)]
pub struct InitializeSession<'info> {
    #[account(init, payer = player, space = 8 + 8 + 32 + 32 + 8 + 8)]
    pub session_data: Account<'info, SessionData>,
    #[account(mut)]
    pub player: Signer<'info>,
    pub system_program: Program<'info, System>,
}

#[derive(Accounts)]
pub struct PurchaseShield<'info> {
    #[account(mut)]
    pub player_token_account: Account<'info, CustomTokenAccount>,
    #[account(mut)]
    pub treasury_account: Account<'info, CustomTokenAccount>,
    #[account(mut, has_one = player)]
    pub session_data: Account<'info, SessionData>,
    #[account(mut)]
    pub config: Account<'info, Config>,
    pub player: Signer<'info>,
    pub player_authority: Signer<'info>,
    pub token_program: Program<'info, Token>,
}

#[derive(Accounts)]
pub struct EarnCb<'info> {
    #[account(mut, has_one = player)]
    pub session_data: Account<'info, SessionData>,
    #[account(mut)]
    pub referrer_session_data: Account<'info, SessionData>,
    #[account(mut)]
    pub config: Account<'info, Config>,
    pub player: Signer<'info>,
}

#[derive(Accounts)]
pub struct ClaimReferralRewards<'info> {
    #[account(mut)]
    pub referrer_session_data: Account<'info, SessionData>,
    #[account(mut)]
    pub treasury_account: Account<'info, CustomTokenAccount>,
    #[account(mut)]
    pub referrer_token_account: Account<'info, CustomTokenAccount>,
    pub treasury_authority: Signer<'info>,
    pub token_program: Program<'info, Token>,
}

#[derive(Accounts)]
pub struct PayToContinue<'info> {
    #[account(mut)]
    pub player_token_account: Account<'info, CustomTokenAccount>,
    #[account(mut)]
    pub treasury_account: Account<'info, CustomTokenAccount>,
    #[account(mut, has_one = player)]
    pub session_data: Account<'info, SessionData>,
    #[account(mut)]
    pub config: Account<'info, Config>,
    pub player: Signer<'info>,
    pub player_authority: Signer<'info>,
    pub token_program: Program<'info, Token>,
}

#[derive(Accounts)]
pub struct WithdrawTokens<'info> {
    #[account(mut)]
    pub vault_account: Account<'info, CustomTokenAccount>,
    #[account(mut)]
    pub player_token_account: Account<'info, CustomTokenAccount>,
    pub session_data: Account<'info, SessionData>,
    pub token_program: Program<'info, Token>,
}

#[derive(Accounts)]
pub struct AdminDeposit<'info> {
    #[account(mut)]
    pub admin_token_account: Account<'info, CustomTokenAccount>,
    #[account(mut)]
    pub vault_account: Account<'info, CustomTokenAccount>,
    pub admin_authority: Signer<'info>,
    pub token_program: Program<'info, Token>,
}

#[derive(Accounts)]
pub struct AdminWithdraw<'info> {
    #[account(mut)]
    pub vault_account: Account<'info, CustomTokenAccount>,
    #[account(mut)]
    pub admin_token_account: Account<'info, CustomTokenAccount>,
    pub admin_authority: Signer<'info>,
    pub token_program: Program<'info, Token>,
}

#[derive(Accounts)]
pub struct CheckBalance<'info> {
    #[account(mut, has_one = player)]
    pub session_data: Account<'info, SessionData>,
    pub player: Signer<'info>,
}

#[derive(Accounts)]
pub struct UpdateReferrer<'info> {
    #[account(mut, has_one = player)]
    pub session_data: Account<'info, SessionData>,
    pub player: Signer<'info>,
}

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