IF OBJECT_ID('dbo.customers', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.customers (
        id BIGINT IDENTITY(1,1) PRIMARY KEY,
        customer_code NVARCHAR(50) NOT NULL,
        name NVARCHAR(150) NOT NULL,
        email NVARCHAR(120) NULL,
        phone NVARCHAR(40) NULL,
        address NVARCHAR(MAX) NULL,
        latitude DECIMAL(10,7) NULL,
        longitude DECIMAL(10,7) NULL,
        status NVARCHAR(20) NOT NULL CONSTRAINT df_customers_status DEFAULT 'active',
        created_at DATETIME2 NOT NULL,
        updated_at DATETIME2 NOT NULL,
        deleted_at DATETIME2 NULL
    );

    CREATE UNIQUE INDEX uq_customers_customer_code ON dbo.customers(customer_code);
    CREATE INDEX idx_customers_status ON dbo.customers(status);
    CREATE INDEX idx_customers_created_at ON dbo.customers(created_at);
    CREATE INDEX idx_customers_geo ON dbo.customers(latitude, longitude);
END;
GO

IF OBJECT_ID('dbo.customer_activities', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.customer_activities (
        id BIGINT IDENTITY(1,1) PRIMARY KEY,
        customer_id BIGINT NOT NULL,
        activity_type NVARCHAR(50) NOT NULL,
        notes NVARCHAR(MAX) NULL,
        created_at DATETIME2 NOT NULL,
        CONSTRAINT fk_customer_activities_customer_id
            FOREIGN KEY (customer_id) REFERENCES dbo.customers(id)
    );
END;
GO
