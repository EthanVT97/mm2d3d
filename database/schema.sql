-- Users table
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    phone_number VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100),
    balance DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) DEFAULT 'active',
    risk_level VARCHAR(20) DEFAULT 'low',
    last_login TIMESTAMP
);

-- Agents table
CREATE TABLE agents (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    balance DECIMAL(10,2) DEFAULT 0.00,
    created_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) DEFAULT 'active',
    last_login TIMESTAMP,
    commission_rate DECIMAL(5,2) DEFAULT 0.00
);

-- Transactions table
CREATE TABLE transactions (
    id SERIAL PRIMARY KEY,
    user_or_agent_id INTEGER NOT NULL,
    type VARCHAR(20) NOT NULL, -- 'deposit' or 'withdraw'
    amount DECIMAL(10,2) NOT NULL,
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) DEFAULT 'pending',
    reference_number VARCHAR(50)
);

-- Playbets table
CREATE TABLE playbets (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    lottery_type VARCHAR(20) NOT NULL, -- '2D', '3D', 'Thai', 'Lao'
    number_selected VARCHAR(20) NOT NULL,
    bet_amount DECIMAL(10,2) NOT NULL,
    result VARCHAR(20), -- 'win', 'lose', 'pending'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    draw_date DATE NOT NULL,
    winning_amount DECIMAL(10,2) DEFAULT 0.00,
    commission_amount DECIMAL(10,2) DEFAULT 0.00,
    settlement_status VARCHAR(20) DEFAULT 'pending'
);

-- Results table
CREATE TABLE results (
    id SERIAL PRIMARY KEY,
    lottery_type VARCHAR(20) NOT NULL,
    winning_numbers VARCHAR(20) NOT NULL,
    draw_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Deposit accounts table
CREATE TABLE deposit_accounts (
    id SERIAL PRIMARY KEY,
    account_number VARCHAR(50) NOT NULL,
    account_type VARCHAR(20) NOT NULL, -- 'bank' or 'wallet'
    bank_name VARCHAR(100),
    account_holder VARCHAR(100),
    added_by INTEGER REFERENCES users(id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT true
);

-- User sessions table for tracking device usage
CREATE TABLE user_sessions (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    device_id VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- User preferences table
CREATE TABLE user_preferences (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    lottery_type VARCHAR(20),
    preferred_numbers TEXT[],
    preferred_bet_amount DECIMAL(10,2),
    preferred_time_slots INTEGER[],
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Risk assessment table
CREATE TABLE risk_assessments (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    risk_level VARCHAR(20),
    risk_factors TEXT[],
    assessment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assessed_by INTEGER REFERENCES agents(id),
    notes TEXT
);

-- Alerts table
CREATE TABLE alerts (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    agent_id INTEGER REFERENCES agents(id),
    alert_type VARCHAR(50),
    severity VARCHAR(20),
    message TEXT,
    is_resolved BOOLEAN DEFAULT false,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP
);

-- User analytics table
CREATE TABLE user_analytics (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id),
    total_bets INTEGER DEFAULT 0,
    total_wins INTEGER DEFAULT 0,
    total_losses INTEGER DEFAULT 0,
    total_deposits DECIMAL(10,2) DEFAULT 0,
    total_withdrawals DECIMAL(10,2) DEFAULT 0,
    last_bet_date TIMESTAMP,
    last_transaction_date TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create indexes for better performance
CREATE INDEX idx_users_phone ON users(phone_number);
CREATE INDEX idx_agents_username ON agents(username);
CREATE INDEX idx_transactions_user ON transactions(user_or_agent_id);
CREATE INDEX idx_playbets_user ON playbets(user_id);
CREATE INDEX idx_results_date ON results(draw_date);
CREATE INDEX idx_sessions_user ON user_sessions(user_id);
CREATE INDEX idx_sessions_device ON user_sessions(device_id);
CREATE INDEX idx_preferences_user ON user_preferences(user_id);
CREATE INDEX idx_risk_user ON risk_assessments(user_id);
CREATE INDEX idx_alerts_user ON alerts(user_id);
CREATE INDEX idx_alerts_agent ON alerts(agent_id);
CREATE INDEX idx_analytics_user ON user_analytics(user_id);

-- Create or replace function to update user analytics
CREATE OR REPLACE FUNCTION update_user_analytics()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO user_analytics (user_id)
    VALUES (NEW.user_id)
    ON CONFLICT (user_id) DO UPDATE
    SET 
        total_bets = user_analytics.total_bets + 1,
        total_wins = CASE WHEN NEW.result = 'win' 
                         THEN user_analytics.total_wins + 1 
                         ELSE user_analytics.total_wins END,
        total_losses = CASE WHEN NEW.result = 'lose' 
                           THEN user_analytics.total_losses + 1 
                           ELSE user_analytics.total_losses END,
        last_bet_date = NEW.created_at,
        updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Create trigger for updating analytics
CREATE TRIGGER update_analytics_on_bet
AFTER INSERT ON playbets
FOR EACH ROW
EXECUTE FUNCTION update_user_analytics();

-- Create or replace function to update transaction analytics
CREATE OR REPLACE FUNCTION update_transaction_analytics()
RETURNS TRIGGER AS $$
BEGIN
    UPDATE user_analytics
    SET 
        total_deposits = CASE WHEN NEW.type = 'deposit' 
                            THEN total_deposits + NEW.amount 
                            ELSE total_deposits END,
        total_withdrawals = CASE WHEN NEW.type = 'withdraw' 
                               THEN total_withdrawals + NEW.amount 
                               ELSE total_withdrawals END,
        last_transaction_date = NEW.date,
        updated_at = CURRENT_TIMESTAMP
    WHERE user_id = NEW.user_or_agent_id;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Create trigger for updating transaction analytics
CREATE TRIGGER update_analytics_on_transaction
AFTER INSERT ON transactions
FOR EACH ROW
EXECUTE FUNCTION update_transaction_analytics();
